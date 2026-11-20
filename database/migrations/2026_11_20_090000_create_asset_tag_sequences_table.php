<?php

use App\Services\AssetTagSequence;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per KKK sequence number in use. The unique index on `sequence`
     * is what keeps the number unique across every inventory module, even
     * when two people save at the same moment.
     */
    public function up(): void
    {
        Schema::create('asset_tag_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('sequence')->unique();
            $table->string('item_type');
            $table->unsignedInteger('item_id');
            $table->timestamps();
            $table->unique(['item_type', 'item_id']);
        });

        // Backfill in id order: the first record keeps a repeated number and
        // the others are logged (run `php artisan snipeit:asset-tag-duplicates`).
        foreach (AssetTagSequence::MODULE_TABLES as $class => $table) {
            DB::table($table)->whereNotNull('asset_tag')->orderBy('id')->select('id', 'asset_tag')
                ->chunkById(500, function ($rows) use ($class) {
                    foreach ($rows as $row) {
                        $sequence = AssetTagSequence::extract($row->asset_tag);
                        if ($sequence === null) {
                            continue;
                        }

                        $inserted = DB::table('asset_tag_sequences')->insertOrIgnore([
                            'sequence' => $sequence,
                            'item_type' => $class,
                            'item_id' => $row->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        if ($inserted === 0) {
                            Log::warning("Asset tag sequence {$sequence} is repeated by {$class} #{$row->id} ({$row->asset_tag}).");
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_tag_sequences');
    }
};
