<?php

namespace App\Services\Mail;

use App\Models\AssetReturn;
use App\Models\AssetShipment;
use App\Services\AssetReplacement;
use App\Services\Backups\CloudBackupSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * What the daily summary mail reports: shipments to confirm, mandatory
 * replacements coming up, returns that stopped moving and backup problems.
 * Only counts leave the application. Details require a signed-in session.
 */
class DailyDigest
{
    /** A backup older than this many hours counts as missing (they run every 6). */
    public const BACKUP_MAX_AGE_HOURS = 13;

    /**
     * @return array<string, array{count: int}>
     */
    public static function sections(): array
    {
        $sections = [
            'shipments' => ['count' => AssetShipment::query()->open()->whereNull('received_at')->whereDate('expected_on', '<=', today())->count()],
            'replacements' => ['count' => AssetReplacement::query([null, today()->addDays(15)->toDateString()])->count()],
            'returns' => ['count' => AssetReturn::query()->stalled()->count()],
            'backups' => ['count' => self::backupProblems()],
        ];

        return array_filter($sections, fn (array $section) => $section['count'] > 0);
    }

    private static function backupProblems(): int
    {
        $problems = 0;

        try {
            $disk = Storage::disk('backup');
            $folder = config('backup.backup.name', 'backups');
            $newest = collect($disk->files($folder))
                ->filter(fn (string $file) => str_ends_with($file, '.zip'))
                ->map(fn (string $file) => $disk->lastModified($file))
                ->max();
            if (! $newest || $newest < now()->subHours(self::BACKUP_MAX_AGE_HOURS)->getTimestamp()) {
                $problems++;
            }
        } catch (Throwable $error) {
            Log::warning('Could not check the local backup for the daily summary.', ['type' => $error::class]);
            $problems++;
        }

        foreach (CloudBackupSettings::results() as $result) {
            if (! ($result['ok'] ?? true)) {
                $problems++;
            }
        }

        return $problems;
    }
}
