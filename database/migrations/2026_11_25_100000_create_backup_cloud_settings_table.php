<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Off-site backup destinations set up from Settings → Backups → Cloud
     * copies. One row. The credentials are encrypted with APP_KEY (the
     * "settings" column); "status" keeps the result of the last copy to
     * each destination.
     */
    public function up(): void
    {
        Schema::create('backup_cloud_settings', function (Blueprint $table) {
            $table->id();
            $table->text('settings')->nullable();
            $table->text('status')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_cloud_settings');
    }
};
