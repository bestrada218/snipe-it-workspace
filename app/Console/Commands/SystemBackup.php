<?php

namespace App\Console\Commands;

use App\Services\Backups\CloudBackupSettings;
use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Backup\Events\BackupZipWasCreated;
use Spatie\Backup\Listeners\EncryptBackupArchive;
use Throwable;
use ZipArchive;

class SystemBackup extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    // --force is accepted for parity with other Laravel commands that require
    // it (migrate, db:seed, etc.); it doesn't gate anything here but external
    // automations were passing it and crashing on the "option does not exist"
    // error.
    protected $signature = 'snipeit:backup {--filename=} {--force} {--local-only : Keep this backup only on the local backup disk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command creates a database dump and zips up all of the uploaded files in the upload directories.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): int
    {
        ini_set('max_execution_time', config('backup.time_limit')); // 600 seconds = 10 minutes

        // Destinations and the archive password saved in Settings → Backups.
        CloudBackupSettings::apply();
        $this->encryptWithCurrentPassword();

        $filename = $this->option('filename') ?: date('Y-m-d-H-i-s');
        if (! str_ends_with($filename, '.zip')) {
            $filename .= '.zip';
        }

        $cloudDisks = array_values(array_diff(config('backup.backup.destination.disks', ['backup']), ['backup']));

        // The archive carries the database and possibly the .env file: it
        // never leaves the server unencrypted. Keep the local copy and fail
        // so monitoring and Settings > Backups report it.
        $copyTo = $this->option('local-only') ? [] : $cloudDisks;
        $password = config('backup.backup.password');
        $localIsRemote = config('filesystems.disks.backup.driver') !== 'local';
        if ($localIsRemote && (blank($password) || config('backup.backup.encryption') !== 'default' || ! EncryptBackupArchive::shouldEncrypt())) {
            $this->error('The primary backup disk is remote and requires archive encryption.');

            return self::FAILURE;
        }
        $refusedCloud = $copyTo !== [] && blank($password);
        if ($refusedCloud) {
            $copyTo = [];
        }

        // spatie writes the archive to the local disk only; each cloud copy is
        // made here, so one failing destination neither stops the others nor
        // hides which one failed.
        $exit = $this->call('backup:run', ['--filename' => $filename, '--only-to-disk' => 'backup']);

        $storedName = config('backup.backup.name', 'backups').'/'.config('backup.backup.destination.filename_prefix').$filename;
        $local = Storage::disk('backup');
        $stored = [];
        $failed = [];
        if ($exit === self::SUCCESS && $local->exists($storedName)) {
            $stored[] = 'backup';
        } else {
            $failed[] = 'backup';
            $copyTo = [];
        }

        $results = [];
        $digest = null;
        $staged = null;
        $stagedPath = null;
        if ($copyTo !== []) {
            try {
                $staged = $this->stageBackup($local, $storedName);
                $stagedPath = stream_get_meta_data($staged)['uri'];
                if (! $this->isEncrypted($stagedPath)) {
                    throw new RuntimeException('Could not verify AES-256 encryption on the backup.');
                }
                $digest = hash_file('sha256', $stagedPath);
                if ($digest === false) {
                    throw new RuntimeException('The staged backup could not be checksummed.');
                }
            } catch (Throwable $error) {
                $failed = array_merge($failed, $copyTo);
                $results = array_fill_keys($copyTo, 'The backup could not be verified as AES-256 encrypted and was not uploaded.');
                $copyTo = [];
                Log::error('Backup verification failed before cloud copy.', ['type' => $error::class]);
            }
        }

        foreach ($copyTo as $disk) {
            try {
                $cloud = Storage::disk($disk);
                $stream = fopen($stagedPath, 'rb');
                if (! is_resource($stream)) {
                    throw new RuntimeException('The local backup could not be read.');
                }
                try {
                    if (! $cloud->writeStream($storedName, $stream)) {
                        throw new RuntimeException('The destination did not accept the backup.');
                    }
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
                if ($this->digest($cloud, $storedName) !== $digest) {
                    throw new RuntimeException('The destination checksum did not match the local backup.');
                }
                $stored[] = $disk;
                $results[$disk] = null;
            } catch (Throwable $error) {
                $failed[] = $disk;
                $results[$disk] = 'The upload or checksum verification failed. Check the destination and its read and write permissions.';
                Log::error('Cloud backup upload or checksum verification failed.', ['disk' => $disk, 'type' => $error::class]);
            }
        }
        if (is_resource($staged)) {
            fclose($staged);
        }

        if ($stored !== []) {
            $this->info('Backup saved on: '.implode(', ', $stored).'.');
        }
        if ($failed !== []) {
            $this->error('Backup failed on: '.implode(', ', $failed).'.');
        }
        if ($refusedCloud) {
            $results += array_fill_keys($cloudDisks, 'Not copied: no backup password is set.');
        }
        CloudBackupSettings::recordResults($this->option('local-only') ? [] : $results);
        if ($refusedCloud) {
            $this->error('Backup not copied to '.implode(', ', $cloudDisks).': set BACKUP_ARCHIVE_PASSWORD to send encrypted copies off the server.');
        }

        return ($failed === [] && ! $refusedCloud) ? self::SUCCESS : self::FAILURE;
    }

    private function isEncrypted(string $path): bool
    {
        $archive = new ZipArchive;
        if ($archive->open($path, ZipArchive::RDONLY) !== true) {
            return false;
        }

        try {
            $hasFiles = false;
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $entry = $archive->statIndex($index);
                if ($entry === false) {
                    return false;
                }
                if (str_ends_with($entry['name'], '/')) {
                    continue;
                }
                if (($entry['encryption_method'] ?? null) !== ZipArchive::EM_AES_256) {
                    return false;
                }
                $hasFiles = true;
            }

            return $hasFiles;
        } finally {
            $archive->close();
        }
    }

    /** @return resource */
    private function stageBackup(FilesystemAdapter $disk, string $path)
    {
        $source = $disk->readStream($path);
        if (! is_resource($source)) {
            throw new RuntimeException('The local backup could not be read.');
        }

        $staged = null;
        try {
            $staged = tmpfile();
            if ($staged === false || stream_copy_to_stream($source, $staged) !== $disk->size($path) || ! fflush($staged)) {
                throw new RuntimeException('The backup could not be staged in full.');
            }

            return $staged;
        } catch (Throwable $error) {
            if (is_resource($staged)) {
                fclose($staged);
            }
            throw $error;
        } finally {
            fclose($source);
        }
    }

    private function digest(FilesystemAdapter $disk, string $path): string
    {
        $stream = $disk->readStream($path);
        if (! is_resource($stream)) {
            throw new RuntimeException('The backup could not be read for verification.');
        }

        try {
            $hash = hash_init('sha256');
            if (hash_update_stream($hash, $stream) !== $disk->size($path)) {
                throw new RuntimeException('The backup could not be read in full.');
            }

            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }

    /**
     * spatie registers its encryption listener at boot, and only when a
     * password was configured then. A password saved in the UI arrives
     * later, so the listener is added here (once) when it is missing.
     */
    private function encryptWithCurrentPassword(): void
    {
        if (! EncryptBackupArchive::shouldEncrypt()) {
            return;
        }

        $listeners = Event::getRawListeners()[BackupZipWasCreated::class] ?? [];
        if (! in_array(EncryptBackupArchive::class, $listeners, true)) {
            Event::listen(BackupZipWasCreated::class, EncryptBackupArchive::class);
        }
    }
}
