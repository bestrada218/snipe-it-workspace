<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * After how many days without progress an open return shows in
     * "Needs attention" and in the daily summary.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('return_stalled_days')->default(7);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('return_stalled_days');
        });
    }
};
