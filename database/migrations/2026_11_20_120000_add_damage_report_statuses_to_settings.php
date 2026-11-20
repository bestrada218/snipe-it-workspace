<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Statuses that need a damage report when an asset is moved into them.
     * Starts with the undeployable statuses whose name says the item is
     * damaged; admins adjust the list in General Settings.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->json('damage_report_statuses')->nullable();
        });

        $damaged = DB::table('status_labels')
            ->whereNull('deleted_at')
            ->where('deployable', 0)->where('pending', 0)->where('archived', 0)
            ->get(['id', 'name'])
            ->filter(fn ($label) => preg_match('/damag|dañ|malo|mala|broken|roto|averiad/iu', $label->name))
            ->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        DB::table('settings')->update(['damage_report_statuses' => json_encode($damaged)]);
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('damage_report_statuses');
        });
    }
};
