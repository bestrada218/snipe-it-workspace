<?php

namespace Tests\Feature\Assets\Api;

use App\Models\Asset;
use App\Models\AssetShipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssetIndexShipmentsTest extends TestCase
{
    public function test_asset_with_open_shipment_is_not_available_for_checkout(): void
    {
        $inTransit = Asset::factory()->create();
        AssetShipment::factory()->create(['asset_id' => $inTransit->id]);
        $delivered = Asset::factory()->create();
        AssetShipment::factory()->assigned()->create(['asset_id' => $delivered->id]);
        $cancelled = Asset::factory()->create();
        AssetShipment::factory()->cancelled()->create(['asset_id' => $cancelled->id]);

        $rows = collect($this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.assets.index', ['limit' => 50]))
            ->assertOk()
            ->json('rows'))
            ->keyBy('id');

        $this->assertFalse($rows[$inTransit->id]['user_can_checkout']);
        $this->assertTrue($rows[$delivered->id]['user_can_checkout']);
        $this->assertTrue($rows[$cancelled->id]['user_can_checkout']);
    }

    public function test_shipment_lookup_does_not_run_once_per_row(): void
    {
        Asset::factory()->count(6)->create();
        $user = User::factory()->superuser()->create();

        DB::enableQueryLog();
        $this->actingAsForApi($user)
            ->getJson(route('api.assets.index', ['limit' => 50]))
            ->assertOk();
        $shipmentQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains($query['query'], 'asset_shipments'));
        DB::disableQueryLog();

        $this->assertCount(1, $shipmentQueries);
    }
}
