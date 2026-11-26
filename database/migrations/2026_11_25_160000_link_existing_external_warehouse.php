<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $setting = DB::table('settings')->whereNull('return_partner_location_id')
                ->whereNotNull('return_partner_name')->lockForUpdate()->first();

            if (! $setting || blank($setting->return_partner_name)) {
                return;
            }

            $name = trim($setting->return_partner_name);
            $location = DB::table('locations')->whereNull('deleted_at')
                ->whereNull('parent_id')->whereNull('company_id')->where('name', $name)->first();

            if ($location && ! $location->is_external) {
                return;
            }

            $locationId = $location->id ?? DB::table('locations')->insertGetId([
                'name' => $name,
                'is_storage' => true,
                'is_external' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('settings')->where('id', $setting->id)->update(['return_partner_location_id' => $locationId]);
        });
    }

    public function down(): void
    {
        // Preserve the linked location, which may now hold inventory.
    }
};
