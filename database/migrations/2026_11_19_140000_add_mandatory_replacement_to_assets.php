<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->date('replacement_due_on')->nullable()->index();
            $table->unsignedSmallInteger('replacement_years')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['replacement_due_on']);
            $table->dropColumn(['replacement_due_on', 'replacement_years']);
        });
    }
};
