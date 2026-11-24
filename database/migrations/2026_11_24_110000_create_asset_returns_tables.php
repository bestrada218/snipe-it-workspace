<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Returns of the items a person holds, for a termination or a change.
     * Each item either goes back to the hardware partner's warehouse (a
     * tracked box shipment) or stays, stored in one of the storage locations.
     */
    public function up(): void
    {
        Schema::create('asset_returns', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->index();
            $table->string('reason', 20);
            $table->string('ticket_number', 100)->nullable();
            $table->integer('ticket_owner_id')->nullable();
            $table->integer('return_owner_id')->nullable();
            // Box shipment back to the partner warehouse (whole return).
            $table->timestamp('address_confirmed_at')->nullable();
            $table->timestamp('boxes_sent_at')->nullable();
            $table->timestamp('boxes_shipped_at')->nullable();
            $table->timestamp('warehouse_arrived_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_return_items', function (Blueprint $table) {
            $table->id();
            $table->integer('asset_return_id')->index();
            $table->string('item_type');
            $table->integer('item_id');
            $table->integer('accessory_checkout_id')->nullable();
            $table->string('destination', 20);
            $table->integer('location_id')->nullable();
            $table->boolean('previously_with_partner')->nullable();
            $table->timestamp('partner_received_at')->nullable();
            $table->timestamp('partner_configured_at')->nullable();
            $table->timestamp('stored_at')->nullable();
            $table->timestamps();
            $table->index(['item_type', 'item_id']);
        });

        Schema::create('asset_return_notes', function (Blueprint $table) {
            $table->id();
            $table->integer('asset_return_id')->index();
            $table->text('note');
            $table->integer('created_by')->nullable();
            $table->timestamps();
        });

        Schema::table('settings', function (Blueprint $table) {
            // Name shown for the hardware partner warehouse (e.g. Rippling)
            // and the location its returned items are assigned to.
            $table->string('return_partner_name', 60)->nullable();
            $table->integer('return_partner_location_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_return_notes');
        Schema::dropIfExists('asset_return_items');
        Schema::dropIfExists('asset_returns');
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['return_partner_name', 'return_partner_location_id']);
        });
    }
};
