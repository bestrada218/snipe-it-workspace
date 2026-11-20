<?php

namespace App\Console\Commands;

use App\Services\AssetTagSequence;
use Illuminate\Console\Command;

class ReportAssetTagDuplicates extends Command
{
    protected $signature = 'snipeit:asset-tag-duplicates';

    protected $description = 'List KKK sequence numbers used by more than one asset tag across the inventory';

    public function handle(): int
    {
        $duplicates = AssetTagSequence::duplicates();
        if ($duplicates->isEmpty()) {
            $this->info(trans('asset-tags.no_duplicates'));

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($duplicates as $sequence => $records) {
            foreach ($records as $record) {
                $rows[] = [sprintf('%03d', $sequence), $record['tag'], $record['type'], $record['id'], $record['deleted'] ? trans('general.yes') : ''];
            }
        }

        $this->warn(trans('asset-tags.duplicates_found', ['count' => $duplicates->count()]));
        $this->table(['KKK', trans('admin/hardware/form.tag'), trans('general.type'), 'ID', trans('general.deleted')], $rows);

        return self::FAILURE;
    }
}
