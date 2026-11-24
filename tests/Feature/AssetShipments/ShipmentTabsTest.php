<?php

namespace Tests\Feature\AssetShipments;

use App\Models\Asset;
use App\Models\AssetShipment;
use App\Models\User;
use Tests\TestCase;

class ShipmentTabsTest extends TestCase
{
    private function shipment(string $tag, ?string $state = null, array $attributes = []): AssetShipment
    {
        $factory = AssetShipment::factory()->for(Asset::factory()->create(['asset_tag' => $tag]));

        return ($state ? $factory->{$state}() : $factory)->create($attributes);
    }

    public function test_in_progress_lists_only_open_shipments(): void
    {
        $this->shipment('NY-901-LP');
        $this->shipment('NY-902-LP', 'assigned');
        $this->shipment('NY-903-LP', 'cancelled');

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('shipments.index'))
            ->assertOk()
            ->assertSee('NY-901-LP')
            ->assertDontSee('NY-902-LP')
            ->assertDontSee('NY-903-LP');
    }

    public function test_past_lists_delivered_and_cancelled_shipments_newest_first(): void
    {
        $this->shipment('NY-911-LP');
        $this->shipment('NY-912-LP', 'assigned', ['received_at' => now()->subDays(5), 'assigned_at' => now()->subDays(5)]);
        $this->shipment('NY-913-LP', 'assigned', ['received_at' => now()->subDay(), 'assigned_at' => now()->subDay()]);
        $this->shipment('NY-914-LP', 'cancelled', ['cancelled_at' => now()->subDays(3)]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('shipments.index', ['view' => 'past']))
            ->assertOk()
            ->assertSee(trans('asset-shipments.closed_on'))
            ->assertDontSee('NY-911-LP')
            ->assertSeeInOrder(['NY-913-LP', 'NY-914-LP', 'NY-912-LP']);
    }

    public function test_deliveries_to_confirm_still_work_with_the_old_link(): void
    {
        $this->shipment('NY-921-LP', null, ['expected_on' => now()->subDay()->toDateString()]);
        $this->shipment('NY-922-LP');

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('shipments.index', ['due' => 1]))
            ->assertOk()
            ->assertSee('NY-921-LP')
            ->assertDontSee('NY-922-LP');
    }

    public function test_each_tab_explains_when_it_is_empty(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('shipments.index', ['view' => 'past']))
            ->assertSee(trans('asset-shipments.empty_past'));
    }
}
