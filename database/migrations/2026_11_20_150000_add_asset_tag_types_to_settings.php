<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PP type codes of the asset tag (XX-KKK-PP[-TTT]), managed in Settings >
     * Asset Tags > Tag types. Starts with the codes from the tag guide.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->json('asset_tag_types')->nullable();
        });

        DB::table('settings')->update(['asset_tag_types' => json_encode([
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
        ])]);
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('asset_tag_types');
        });
    }
};
