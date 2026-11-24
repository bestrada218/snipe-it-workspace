<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The box shipment back to the partner is handled on another platform:
     * keep its carrier and tracking number with the return.
     */
    public function up(): void
    {
        Schema::table('asset_returns', function (Blueprint $table) {
            $table->string('carrier', 100)->nullable()->after('return_owner_id');
            $table->string('tracking_number', 100)->nullable()->after('carrier');
        });
    }

    public function down(): void
    {
        Schema::table('asset_returns', function (Blueprint $table) {
            $table->dropColumn(['carrier', 'tracking_number']);
        });
    }
};
