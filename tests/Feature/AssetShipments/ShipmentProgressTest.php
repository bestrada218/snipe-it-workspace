<?php

namespace Tests\Feature\AssetShipments;

use App\Models\AssetShipment;
use App\Models\User;
use Tests\TestCase;

class ShipmentProgressTest extends TestCase
{
    public function test_in_transit_shipment_marks_the_transit_step_as_current(): void
    {
        $shipment = AssetShipment::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('shipments.show', $shipment))
            ->assertOk()
            ->assertSeeInOrder([
                trans('asset-shipments.step_registered'),
                'aria-current="step"',
                trans('asset-shipments.step_in_transit'),
                trans('asset-shipments.step_received'),
                trans('asset-shipments.step_assigned'),
            ], false);
    }

    public function test_header_leads_with_the_asset_tag(): void
    {
        $shipment = AssetShipment::factory()->create();
        $shipment->asset->update(['asset_tag' => 'NY-053-LP-W11']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('shipments.show', $shipment))
            ->assertOk()
            ->assertSee('NY-053-LP-W11 · ', false);
    }

    public function test_cancelled_shipment_says_so(): void
    {
        $shipment = AssetShipment::factory()->cancelled()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('shipments.show', $shipment))
            ->assertOk()
            ->assertSee('is-cancelled', false);
    }
}
