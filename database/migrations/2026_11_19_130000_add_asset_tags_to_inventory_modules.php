<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['accessories', 'components', 'consumables', 'licenses'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $schema) {
                $schema->string('asset_tag')->nullable();
            });
        }

        DB::table('accessories')->where('notes', 'like', '%Placa: %')
            ->select('id', 'notes')->chunkById(250, function ($accessories) {
                foreach ($accessories as $accessory) {
                    if (preg_match('/^Placa:\h*([A-Z0-9]{2}-\d{3}-[A-Z0-9]{2}(?:-[A-Z0-9]{3})?)\h*\r?$/mi', $accessory->notes, $match)) {
                        DB::table('accessories')->where('id', $accessory->id)->update(['asset_tag' => strtoupper($match[1])]);
                    }
                }
            });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $schema) {
                $schema->dropColumn('asset_tag');
            });
        }
    }
};
