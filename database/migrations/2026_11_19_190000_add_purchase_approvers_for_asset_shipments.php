<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->json('purchase_approvers')->nullable();
        });

        Schema::table('asset_shipments', function (Blueprint $table) {
            $table->unsignedInteger('purchase_approver_id')->nullable();
            $table->string('purchase_approver_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('asset_shipments', function (Blueprint $table) {
            $table->dropColumn(['purchase_approver_id', 'purchase_approver_name']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('purchase_approvers');
        });
    }
};
