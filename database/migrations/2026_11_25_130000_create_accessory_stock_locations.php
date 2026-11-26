<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accessory_stock_locations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('accessory_id');
            $table->unsignedInteger('location_id');
            $table->index(['accessory_id', 'location_id']);
            $table->foreign('accessory_id')->references('id')->on('accessories')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accessory_stock_locations');
    }
};
