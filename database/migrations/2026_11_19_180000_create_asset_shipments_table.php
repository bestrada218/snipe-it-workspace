<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('asset_id')->index();
            $table->unsignedInteger('recipient_id')->index();
            $table->unsignedInteger('created_by');
            $table->string('platform', 120);
            $table->string('carrier', 120)->nullable();
            $table->string('tracking_number', 150);
            $table->text('approval_reference');
            $table->string('approval_key');
            $table->string('approval_filename');
            $table->date('expected_on')->index();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_shipments');
    }
};
