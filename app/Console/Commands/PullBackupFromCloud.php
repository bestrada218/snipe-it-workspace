<?php

namespace App\Console\Commands;

use App\Services\Backups\CloudBackupSettings;
use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Brings a backup stored on a cloud disk (backup_s3, backup_google) back to
 * the local backup disk, where Settings > Backups and snipeit:restore can
 * use it. Meant for a rebuilt server that has no local backups.
 */
class PullBackupFromCloud extends Command
{
    protected $signature = 'snipeit:backup-pull
                            {disk : Cloud disk to read from, e.g. backup_s3 or backup_google}
                            {file? : Backup file name to bring back; omit to list what is there}
                            {--password= : Password used to encrypt an older archive}
                            {--latest : Bring back the most recent backup}';

    protected $description = 'List the backups on a cloud disk or copy one back to the local backup disk.';

    public function handle(): int
    {
        CloudBackupSettings::apply();
        $diskName = $this->argument('disk');
        if (! in_array($diskName, CloudBackupSettings::DISKS, true)) {
            $this->error("Unknown cloud disk [{$diskName}]. Use one of: ".implode(', ', CloudBackupSettings::DISKS));

            return self::FAILURE;
        }

        try {
            return $this->fetchBackup($diskName);
        } catch (Throwable $error) {
            Log::error('Could not retrieve cloud backup.', ['disk' => $diskName, 'type' => $error::class]);
            $this->error('Could not retrieve the cloud backup. Check the destination and local disk.');

            return self::FAILURE;
        }
    }

    private function fetchBackup(string $diskName): int
    {
        $folder = config('backup.backup.name', 'backups');
        $cloud = Storage::disk($diskName);
        $backups = collect($cloud->files($folder))
            ->filter(fn (string $path) => str_ends_with($path, '.zip'))
            ->sortByDesc(fn (string $path) => $cloud->lastModified($path))
            ->values();

        if ($backups->isEmpty()) {
            $this->warn("No backups found on [{$diskName}].");

            return self::FAILURE;
        }

        $file = $this->argument('file');
        if (! $file && ! $this->option('latest')) {
            $this->table(['Backup', 'Size (MB)', 'Date'], $backups->map(fn (string $path) => [
                basename($path),
                round($cloud->size($path) / 1048576, 2),
                date('Y-m-d H:i', $cloud->lastModified($path)),
            ])->all());

            return self::SUCCESS;
        }

        $source = $file ? $folder.'/'.basename($file) : $backups->first();
        if (! str_ends_with($source, '.zip') || ! $cloud->exists($source)) {
            $this->error("Backup [{$source}] is not on [{$diskName}].");

            return self::FAILURE;
        }

        $local = Storage::disk('backup');
        if ($local->exists($source)) {
            $this->error('A local backup already has this name. Keep or rename that copy before downloading another.');

            return self::FAILURE;
        }

        $temporary = tmpfile();
        if ($temporary === false) {
            throw new RuntimeException('Unable to create a temporary backup file.');
        }
        try {
            $remote = $cloud->readStream($source);
            if (! is_resource($remote)) {
                throw new RuntimeException('Unable to read the cloud backup.');
            }
            try {
                if (stream_copy_to_stream($remote, $temporary) !== $cloud->size($source) || ! fflush($temporary)) {
                    throw new RuntimeException('Unable to read the entire cloud backup.');
                }
            } finally {
                fclose($remote);
            }

            $password = $this->option('password') ?: config('backup.backup.password');
            if ($problem = RestoreFromBackup::archiveProblem(stream_get_meta_data($temporary)['uri'], $password)) {
                $this->error($problem);

                return self::FAILURE;
            }

            rewind($temporary);
            $this->publishBackup($local, $source, $temporary);
        } finally {
            if (is_resource($temporary)) {
                fclose($temporary);
            }
        }

        $this->info('Copied '.basename($source).' to the local backup disk.');
        if (config('filesystems.disks.backup.driver') === 'local') {
            $this->line('Restore it from Settings > Backups or with: php artisan snipeit:restore '.$local->path($source));
        }

        return self::SUCCESS;
    }

    /** @param resource $stream */
    private function publishBackup(FilesystemAdapter $local, string $source, $stream): void
    {
        $staged = $source.'.'.Str::random(16).'.part';
        try {
            $size = fstat($stream)['size'];
            if (! $local->writeStream($staged, $stream) || $local->size($staged) !== $size ||
                $local->exists($source) || ! $local->move($staged, $source)) {
                throw new RuntimeException('Unable to save the verified backup.');
            }
        } finally {
            $local->delete($staged);
        }
    }
}
