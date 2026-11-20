<?php

namespace App\Services;

use App\Models\Actionlog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The PP part of an asset tag (XX-KKK-PP[-TTT]): which item types exist,
 * what each code means and which ones take a TTT suffix. Managed in
 * Settings > Asset Tags > Tag types.
 */
final class AssetTagTypes
{
    /**
     * Used until the settings hold a list (fresh installs before migrating).
     *
     * @var list<array{code: string, name: string, suffix: bool}>
     */
    public const DEFAULTS = [
        ['code' => 'LP', 'name' => 'Laptop', 'suffix' => true],
        ['code' => 'DT', 'name' => 'Desktop', 'suffix' => true],
        ['code' => 'HS', 'name' => 'Headset', 'suffix' => false],
        ['code' => 'KB', 'name' => 'Keyboard', 'suffix' => false],
        ['code' => 'MS', 'name' => 'Mouse', 'suffix' => false],
        ['code' => 'WC', 'name' => 'Webcam', 'suffix' => false],
        ['code' => 'DK', 'name' => 'Docking station', 'suffix' => false],
        ['code' => 'TV', 'name' => 'Television', 'suffix' => false],
        ['code' => 'SP', 'name' => 'Speaker', 'suffix' => false],
        ['code' => 'PJ', 'name' => 'Projector', 'suffix' => false],
        ['code' => 'AP', 'name' => 'Access point', 'suffix' => false],
        ['code' => 'VC', 'name' => 'Video conferencing camera', 'suffix' => false],
    ];

    public const CODE_PATTERN = '/^[A-Z0-9]{2}$/';

    /**
     * @return list<array{code: string, name: string, suffix: bool}>
     */
    public static function all(): array
    {
        $types = Setting::getSettings()?->asset_tag_types;

        return is_array($types) && $types !== [] ? array_values($types) : self::DEFAULTS;
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_column(self::all(), 'code');
    }

    /**
     * Codes whose tags may end with a TTT suffix (computers).
     *
     * @return list<string>
     */
    public static function suffixCodes(): array
    {
        return array_values(array_column(array_filter(self::all(), fn (array $type) => $type['suffix']), 'code'));
    }

    /**
     * How many records (deleted ones included) carry each code in their tag.
     *
     * @return array<string, int>
     */
    public static function usage(): array
    {
        $usage = array_fill_keys(self::codes(), 0);
        foreach (AssetTagSequence::MODULE_TABLES as $table) {
            DB::table($table)->where('asset_tag', 'like', '__-___-__%')->pluck('asset_tag')
                ->each(function (?string $tag) use (&$usage) {
                    $code = self::codeOf($tag);
                    if ($code !== null && array_key_exists($code, $usage)) {
                        $usage[$code]++;
                    }
                });
        }

        return $usage;
    }

    public static function codeOf(?string $tag): ?string
    {
        if (AssetTagSequence::extract($tag) === null) {
            return null;
        }

        return explode('-', strtoupper(trim($tag)))[2];
    }

    /**
     * Saves the list of types. A changed code is applied to every tag that
     * used the old one, in every module, and each change is written to the
     * record's history. A type still used by tags cannot be removed.
     *
     * @param  list<array{original: ?string, code: string, name: string, suffix: bool, remove: bool}>  $rows
     * @return int Number of tags that changed
     */
    public static function save(array $rows, ?User $actor): int
    {
        $usage = self::usage();
        $kept = [];
        $renames = [];

        foreach ($rows as $index => $row) {
            $original = $row['original'] ?: null;
            if ($row['remove']) {
                if ($original !== null && ($usage[$original] ?? 0) > 0) {
                    throw ValidationException::withMessages([
                        "types.$index.remove" => trans('asset-tag-types.in_use', ['code' => $original, 'count' => $usage[$original]]),
                    ]);
                }

                continue;
            }

            $kept[] = ['code' => $row['code'], 'name' => $row['name'], 'suffix' => $row['suffix']];
            if ($original !== null && $original !== $row['code']) {
                $renames[$original] = $row['code'];
            }
        }

        if ($kept === []) {
            throw ValidationException::withMessages(['types' => trans('asset-tag-types.at_least_one')]);
        }

        return DB::transaction(function () use ($kept, $renames, $actor): int {
            $changed = $renames === [] ? 0 : self::renameInTags($renames, $actor);

            $setting = Setting::getSettings();
            $setting->asset_tag_types = $kept;
            $setting->save();

            return $changed;
        });
    }

    /**
     * Every rename is computed from the tag as it was, so swapping two codes
     * (LP <-> DT) works in one save.
     *
     * @param  array<string, string>  $renames  old code => new code
     */
    private static function renameInTags(array $renames, ?User $actor): int
    {
        $changed = 0;
        foreach (AssetTagSequence::MODULE_TABLES as $class => $table) {
            $rows = DB::table($table)->where('asset_tag', 'like', '__-___-__%')->lockForUpdate()->get(['id', 'asset_tag']);
            foreach ($rows as $row) {
                $code = self::codeOf($row->asset_tag);
                if ($code === null || ! isset($renames[$code])) {
                    continue;
                }

                $parts = explode('-', strtoupper(trim($row->asset_tag)));
                $parts[2] = $renames[$code];
                $newTag = implode('-', $parts);

                DB::table($table)->where('id', $row->id)->update(['asset_tag' => $newTag, 'updated_at' => now()]);

                $log = new Actionlog;
                $log->item_type = $class;
                $log->item_id = $row->id;
                $log->created_by = $actor?->id;
                $log->action_date = now();
                $log->note = trans('asset-tag-types.history_note', ['old' => $code, 'new' => $renames[$code]]);
                $log->log_meta = json_encode(['asset_tag' => ['old' => $row->asset_tag, 'new' => $newTag]]);
                $log->logaction('update');

                $changed++;
            }
        }

        return $changed;
    }
}
