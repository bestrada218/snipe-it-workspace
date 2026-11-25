<?php

namespace App\Services\Mail;

use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\AssetShipment;
use App\Services\AssetReplacement;
use App\Services\Backups\CloudBackupSettings;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * What the daily summary mail reports: shipments to confirm, mandatory
 * replacements coming up, returns that stopped moving and backup problems.
 * Each section lists at most a few items with links; the counts are exact.
 */
class DailyDigest
{
    public const LIMIT = 10;

    /** A backup older than this many hours counts as missing (they run every 6). */
    public const BACKUP_MAX_AGE_HOURS = 13;

    /**
     * @return array<string, array{count: int, items: list<array{label: string, detail: string, url: string}>}>
     */
    public static function sections(): array
    {
        $sections = [
            'shipments' => self::shipments(),
            'replacements' => self::replacements(),
            'returns' => self::returns(),
            'backups' => self::backups(),
        ];

        return array_filter($sections, fn (array $section) => $section['count'] > 0);
    }

    private static function shipments(): array
    {
        $query = AssetShipment::query()->open()->whereNull('received_at')->whereDate('expected_on', '<=', today());

        return [
            'count' => (clone $query)->count(),
            'items' => $query->with(['asset', 'recipient'])->orderBy('expected_on')->limit(self::LIMIT)->get()
                ->map(fn (AssetShipment $shipment) => [
                    'label' => trim(($shipment->asset?->asset_tag ?? '').' → '.($shipment->recipient?->display_name ?? '')),
                    'detail' => trans('mail-settings.digest.expected', ['date' => $shipment->expected_on->toDateString()]),
                    'url' => route('shipments.show', $shipment),
                ])->all(),
        ];
    }

    private static function replacements(): array
    {
        $query = AssetReplacement::query([null, today()->addDays(15)->toDateString()]);

        return [
            'count' => (clone $query)->count(),
            'items' => $query->orderBy('assets.replacement_due_on')->limit(self::LIMIT)->get()
                ->map(fn (Asset $asset) => [
                    'label' => trim($asset->asset_tag.' · '.($asset->name ?: $asset->model?->name)),
                    'detail' => trans('mail-settings.digest.replace_by', ['date' => $asset->replacement_due_on?->toDateString()]),
                    'url' => route('hardware.show', $asset),
                ])->all(),
        ];
    }

    private static function returns(): array
    {
        $query = AssetReturn::query()->stalled();

        return [
            'count' => (clone $query)->count(),
            'items' => $query->with(['person', 'items', 'notes'])->orderBy('updated_at')->limit(self::LIMIT)->get()
                ->map(fn (AssetReturn $return) => [
                    'label' => trans('asset-returns.number', ['id' => $return->id]).' · '.$return->person?->display_name,
                    'detail' => trans('asset-returns.days_without_progress', ['days' => (int) $return->lastActivityAt()?->diffInDays(now())]),
                    'url' => route('returns.show', $return),
                ])->all(),
        ];
    }

    private static function backups(): array
    {
        $problems = [];

        try {
            $disk = Storage::disk('backup');
            $folder = config('backup.backup.name', 'backups');
            $newest = collect($disk->files($folder))
                ->filter(fn (string $file) => str_ends_with($file, '.zip'))
                ->map(fn (string $file) => $disk->lastModified($file))
                ->max();
            if (! $newest || $newest < now()->subHours(self::BACKUP_MAX_AGE_HOURS)->getTimestamp()) {
                $problems[] = [
                    'label' => trans('mail-settings.digest.backup_missing'),
                    'detail' => $newest ? trans('mail-settings.digest.backup_last', ['date' => date('Y-m-d H:i', $newest)]) : '',
                    'url' => route('settings.backups.index'),
                ];
            }
        } catch (Throwable $error) {
            $problems[] = ['label' => trans('mail-settings.digest.backup_missing'), 'detail' => $error->getMessage(), 'url' => route('settings.backups.index')];
        }

        foreach (CloudBackupSettings::results() as $destination => $result) {
            if (! ($result['ok'] ?? true)) {
                $problems[] = [
                    'label' => trans('mail-settings.digest.cloud_failed', ['destination' => $destination]),
                    'detail' => (string) ($result['error'] ?? ''),
                    'url' => route('settings.backups.index'),
                ];
            }
        }

        return ['count' => count($problems), 'items' => $problems];
    }
}
