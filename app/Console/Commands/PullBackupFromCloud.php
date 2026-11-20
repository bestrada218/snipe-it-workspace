<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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
                            {--latest : Bring back the most recent backup}';

    protected $description = 'List the backups on a cloud disk or copy one back to the local backup disk.';

    public function handle(): int
    {
        $diskName = $this->argument('disk');
        if ($diskName === 'backup' || ! array_key_exists($diskName, config('filesystems.disks', []))) {
            $this->error("Unknown cloud disk [{$diskName}]. Use one of: ".implode(', ', array_diff(config('backup.backup.destination.disks', []), ['backup'])));

            return self::FAILURE;
        }

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
        if (! $cloud->exists($source)) {
            $this->error("Backup [{$source}] is not on [{$diskName}].");

            return self::FAILURE;
        }

        $local = Storage::disk('backup');
        $local->writeStream($source, $cloud->readStream($source));

        $this->info('Copied '.basename($source).' to the local backup disk.');
        if (config('filesystems.disks.backup.driver') === 'local') {
            $this->line('Restore it from Settings > Backups or with: php artisan snipeit:restore '.$local->path($source));
        }

        return self::SUCCESS;
    }
}
