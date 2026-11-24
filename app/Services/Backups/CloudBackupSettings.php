<?php

namespace App\Services\Backups;

use App\Models\BackupCloudSetting;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cloud backup destinations set up from the UI (Settings → Backups → Cloud
 * copies). They are added to the ones in .env (BACKUP_CLOUD_DISKS) and,
 * for a destination enabled here, its saved values replace the .env ones.
 *
 * Every command that makes, pulls or restores a backup calls apply() first,
 * so the runtime config already holds these values.
 */
class CloudBackupSettings
{
    /** UI destination => filesystem disk (config/filesystems.php). */
    public const DISKS = ['s3' => 'backup_s3', 'google' => 'backup_google'];

    /** Values that are never shown back in the form. */
    public const SECRETS = ['password', 's3.secret', 'google.service_account_json'];

    /**
     * The saved settings, or an empty set when nothing was saved, the table
     * does not exist yet or the row cannot be decrypted.
     *
     * @return array{password?: ?string, s3?: array<string, mixed>, google?: array<string, mixed>}
     */
    public static function saved(): array
    {
        try {
            return self::row()?->settings ?? [];
        } catch (DecryptException) {
            Log::warning('Cloud backup settings cannot be decrypted: APP_KEY changed since they were saved.');

            return [];
        }
    }

    /** The saved row could not be read with the current APP_KEY. */
    public static function unreadable(): bool
    {
        try {
            self::row()?->settings;

            return false;
        } catch (DecryptException) {
            return true;
        }
    }

    /**
     * Put the saved destinations and archive password into the runtime config.
     */
    public static function apply(): void
    {
        $saved = self::saved();
        if ($saved === []) {
            return;
        }

        if (filled($saved['password'] ?? null)) {
            config(['backup.backup.password' => $saved['password']]);
        }

        $disks = config('backup.backup.destination.disks', ['backup']);
        foreach (self::DISKS as $key => $disk) {
            if (! ($saved[$key]['enabled'] ?? false)) {
                continue;
            }
            $disks[] = $disk;
            foreach (self::diskConfig($key, $saved[$key]) as $option => $value) {
                config(["filesystems.disks.{$disk}.{$option}" => $value]);
            }
            // A disk already built keeps its old credentials otherwise.
            Storage::forgetDisk($disk);
        }

        config(['backup.backup.destination.disks' => array_values(array_unique($disks))]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function diskConfig(string $key, array $values): array
    {
        if ($key === 's3') {
            return array_filter([
                'key' => $values['key'] ?? null,
                'secret' => $values['secret'] ?? null,
                'region' => $values['region'] ?? null,
                'bucket' => $values['bucket'] ?? null,
                'endpoint' => $values['endpoint'] ?? null,
                'root' => $values['root'] ?? null,
            ], 'filled') + ['use_path_style_endpoint' => (bool) ($values['path_style'] ?? false)];
        }

        return array_filter([
            'service_account_json' => $values['service_account_json'] ?? null,
            'folder_id' => $values['folder_id'] ?? null,
        ], 'filled') + ['shared_drive' => (bool) ($values['shared_drive'] ?? false)];
    }

    /**
     * Save the form. A blank secret keeps the one already saved.
     *
     * @param  array<string, mixed>  $input
     */
    public static function save(array $input, User $actor): void
    {
        $saved = self::saved();

        $settings = [
            'password' => filled($input['password'] ?? null) ? $input['password'] : ($saved['password'] ?? null),
            's3' => [
                'enabled' => (bool) ($input['s3']['enabled'] ?? false),
                'key' => trim((string) ($input['s3']['key'] ?? '')),
                'secret' => filled($input['s3']['secret'] ?? null) ? trim($input['s3']['secret']) : ($saved['s3']['secret'] ?? null),
                'region' => trim((string) ($input['s3']['region'] ?? '')),
                'bucket' => trim((string) ($input['s3']['bucket'] ?? '')),
                'endpoint' => trim((string) ($input['s3']['endpoint'] ?? '')),
                'path_style' => (bool) ($input['s3']['path_style'] ?? false),
                'root' => trim((string) ($input['s3']['root'] ?? '')),
            ],
            'google' => [
                'enabled' => (bool) ($input['google']['enabled'] ?? false),
                'service_account_json' => filled($input['google']['service_account_json'] ?? null)
                    ? trim($input['google']['service_account_json'])
                    : ($saved['google']['service_account_json'] ?? null),
                'folder_id' => trim((string) ($input['google']['folder_id'] ?? '')),
                'shared_drive' => (bool) ($input['google']['shared_drive'] ?? false),
            ],
        ];

        $row = self::row() ?? new BackupCloudSetting;
        $row->settings = $settings;
        $row->updated_by = $actor->id;
        $row->save();
    }

    /**
     * Whether a secret is already saved (the form shows "saved" instead of it).
     */
    public static function has(string $secret): bool
    {
        return filled(data_get(self::saved(), $secret));
    }

    /**
     * Write and delete a small file on a destination.
     *
     * @return string|null why it failed, or null when it worked
     */
    public static function test(string $key): ?string
    {
        self::apply();
        $disk = self::DISKS[$key] ?? null;
        if (! $disk) {
            return 'Unknown destination.';
        }

        $probe = config('backup.backup.name', 'backups').'/.connection-test-'.Str::random(8).'.txt';
        try {
            $storage = Storage::disk($disk);
            $storage->put($probe, 'Snipe-IT Workspace connection test');
            $found = $storage->exists($probe);
            $storage->delete($probe);

            return $found ? null : 'The test file was not there after writing it.';
        } catch (Throwable $error) {
            return $error->getMessage();
        }
    }

    /**
     * Remember how the last copy to each disk went (shown in the UI).
     *
     * @param  array<string, string|null>  $results  disk => error message, or null when it worked
     */
    public static function recordResults(array $results): void
    {
        if ($results === []) {
            return;
        }

        try {
            if (! Schema::hasTable('backup_cloud_settings')) {
                return;
            }
            $row = self::row() ?? new BackupCloudSetting;
            $status = $row->status ?? [];
            foreach ($results as $disk => $error) {
                $status[$disk] = ['at' => now()->toIso8601String(), 'ok' => $error === null, 'error' => $error ? Str::limit($error, 500) : null];
            }
            $row->status = $status;
            $row->save();
        } catch (Throwable $error) {
            Log::warning('Could not record the cloud backup result: '.$error->getMessage());
        }
    }

    /**
     * @return array<string, array{at: string, ok: bool, error: ?string}>
     */
    public static function results(): array
    {
        return self::row()?->status ?? [];
    }

    public static function row(): ?BackupCloudSetting
    {
        try {
            return BackupCloudSetting::query()->first();
        } catch (Throwable) {
            // Table not migrated yet (fresh install or older database).
            return null;
        }
    }
}
