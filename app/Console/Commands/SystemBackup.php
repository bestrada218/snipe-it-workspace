<?php

namespace App\Console\Commands;

use App\Services\Backups\CloudBackupSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Backup\Events\BackupZipWasCreated;
use Spatie\Backup\Listeners\EncryptBackupArchive;
use Throwable;

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
        $refusedCloud = $cloudDisks !== [] && blank(config('backup.backup.password'));
        $copyTo = ($this->option('local-only') || $refusedCloud) ? [] : $cloudDisks;

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
        foreach ($copyTo as $disk) {
            try {
                $cloud = Storage::disk($disk);
                $cloud->writeStream($storedName, $local->readStream($storedName));
                if (! $cloud->exists($storedName)) {
                    throw new RuntimeException('the file is not there after the upload');
                }
                $stored[] = $disk;
                $results[$disk] = null;
            } catch (Throwable $error) {
                $failed[] = $disk;
                $results[$disk] = $error->getMessage();
                $this->error("Copying the backup to {$disk} failed: {$error->getMessage()}");
                Log::error("Backup copy to {$disk} failed: {$error->getMessage()}");
            }
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
