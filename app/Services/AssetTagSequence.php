<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

final class AssetTagSequence
{
    public static function next(): ?string
    {
        $highest = 0;

        // Include archived and deleted records and all locations. Only the
        // resulting number is displayed to the person entering the tag.
        foreach (Setting::INVENTORY_MODULES as $model) {
            $table = (new $model)->getTable();
            $tags = DB::table($table)->select('asset_tag')->where('asset_tag', 'like', '__-___-__%')->cursor();

            foreach ($tags as $row) {
                if (preg_match('/^[A-Z0-9]{2}-(\d{3})-[A-Z0-9]{2}(?:-[A-Z0-9]{3})?$/iD', trim($row->asset_tag), $parts)) {
                    $highest = max($highest, (int) $parts[1]);
                }
            }
        }

        return $highest < 999 ? sprintf('%03d', $highest + 1) : null;
    }

    public static function hint(): string
    {
        $next = self::next();

        return $next === null
            ? trans('asset-tags.sequence_exhausted')
            : trans('asset-tags.next_sequence', ['sequence' => $next]);
    }
}
