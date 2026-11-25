<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Outgoing mail set up from Settings → Email (one row): the SMTP server
     * (encrypted with APP_KEY, it holds a password), the daily summary and
     * the result of the last test and summary.
     */
    public function up(): void
    {
        Schema::create('mail_settings', function (Blueprint $table) {
            $table->id();
            $table->text('settings')->nullable();
            $table->text('digest')->nullable();
            $table->text('status')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_settings');
    }
};
