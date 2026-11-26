<?php

namespace App\Services;

use App\Models\Accessory;
use App\Models\Location;
use App\Models\LocationGroupScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AccessoryStockLocations
{
    public static function putAway(Accessory $accessory, Location $location): void
    {
        DB::table('accessory_stock_locations')->insert([
            'accessory_id' => $accessory->id,
            'location_id' => $location->id,
        ]);
    }

    public static function balances(Accessory $accessory): Collection
    {
        $allowed = LocationGroupScope::allowedIds(LocationGroupScope::currentUser());

        return DB::table('accessory_stock_locations as stock')
            ->join('locations', 'locations.id', '=', 'stock.location_id')
            ->where('stock.accessory_id', $accessory->id)
            ->when($allowed !== null, fn ($query) => $query->whereIn('locations.id', $allowed))
            ->groupBy('locations.id', 'locations.name')
            ->orderBy('locations.name')
            ->selectRaw('locations.id, locations.name, count(*) as units')
            ->get();
    }

    public static function trackedCount(Accessory $accessory): int
    {
        return DB::table('accessory_stock_locations')->where('accessory_id', $accessory->id)->count();
    }

    public static function take(Accessory $accessory, ?int $locationId, int $remainingBeforeInsert): void
    {
        $stock = DB::table('accessory_stock_locations')->where('accessory_id', $accessory->id);
        $tracked = $stock->count();
        if ($locationId === null && ($tracked === 0 || $remainingBeforeInsert > $tracked)) {
            return;
        }

        $allowed = LocationGroupScope::allowedIds(LocationGroupScope::currentUser());
        $unit = $stock->when($locationId !== null, fn ($query) => $query->where('location_id', $locationId))
            ->when($allowed !== null, fn ($query) => $query->whereIn('location_id', $allowed))
            ->orderBy('id')->lockForUpdate()->first();
        if ($unit === null) {
            throw ValidationException::withMessages(['stock_location_id' => trans('admin/accessories/general.stock_location_unavailable')]);
        }

        DB::table('accessory_stock_locations')->where('id', $unit->id)->delete();
    }
}
