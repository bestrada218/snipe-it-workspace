<?php

namespace Tests\Feature\AssetReturns;

use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Location;
use App\Models\User;
use App\Services\AssetReturns;
use Tests\TestCase;

class AssetReturnFlowTest extends TestCase
{
    private User $person;

    private User $admin;

    private Asset $laptop;

    private Asset $monitor;

    private AccessoryCheckout $headsetCheckout;

    private Location $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings->set(['return_partner_name' => 'Rippling']);
        $this->person = User::factory()->create();
        $this->admin = User::factory()->superuser()->create();
        $this->laptop = Asset::factory()->assignedToUser($this->person)->create(['asset_tag' => 'NY-301-LP-W11']);
        $this->monitor = Asset::factory()->assignedToUser($this->person)->create(['asset_tag' => 'NY-302-TV']);
        $headset = Accessory::factory()->create(['qty' => 5, 'asset_tag' => 'NY-303-HS']);
        $this->headsetCheckout = AccessoryCheckout::create(['accessory_id' => $headset->id, 'assigned_to' => $this->person->id, 'assigned_type' => User::class]);
        $this->storage = Location::factory()->create(['name' => 'Midtown clinic']);
    }

    private function createReturn(array $overrides = []): AssetReturn
    {
        $this->actingAs($this->admin)->post(route('returns.store'), array_replace_recursive([
            'user_id' => $this->person->id,
            'reason' => 'termination',
            'ticket_number' => 'INC-4321',
            'ticket_owner_id' => $this->admin->id,
            'return_owner_id' => $this->admin->id,
            'note' => 'Charger missing',
            'items' => [
                'asset-'.$this->laptop->id => ['include' => 1, 'destination' => 'partner', 'previously_with_partner' => '0'],
                'asset-'.$this->monitor->id => ['include' => 1, 'destination' => 'storage', 'location_id' => $this->storage->id],
                'accessory-'.$this->headsetCheckout->id => ['include' => 1, 'destination' => 'storage', 'location_id' => $this->storage->id],
            ],
        ], $overrides))->assertSessionHasNoErrors();

        return AssetReturn::latest('id')->firstOrFail();
    }

    public function test_choosing_a_person_loads_what_they_hold(): void
    {
        $this->actingAs($this->admin)
            ->get(route('returns.create', ['user_id' => $this->person->id]))
            ->assertOk()
            ->assertSee('NY-301-LP-W11')
            ->assertSee('NY-302-TV')
            ->assertSee('NY-303-HS');
    }

    public function test_a_return_records_items_owners_and_copies_the_note_to_each_item(): void
    {
        $return = $this->createReturn();

        $this->assertSame(3, $return->items()->count());
        $this->assertSame($this->admin->id, $return->ticket_owner_id);
        foreach ([$this->laptop, $this->monitor] as $asset) {
            $this->assertTrue(Actionlog::where('item_type', Asset::class)->where('item_id', $asset->id)
                ->where('action_type', 'note added')->where('note', 'like', '%Charger missing%')->exists());
        }

        $this->actingAs($this->admin)->post(route('returns.notes', $return), ['note' => 'Boxes requested'])->assertSessionHasNoErrors();
        $this->assertSame(2, Actionlog::where('item_type', Asset::class)->where('item_id', $this->laptop->id)->where('action_type', 'note added')->count());
    }

    public function test_items_already_in_an_open_return_are_not_offered_again(): void
    {
        $this->createReturn();

        $this->actingAs($this->admin)
            ->get(route('returns.create', ['user_id' => $this->person->id]))
            ->assertDontSee('NY-301-LP-W11')
            ->assertSee(trans('asset-returns.no_items'));
    }

    public function test_storing_an_item_leaves_it_available_in_the_clinic_warehouse(): void
    {
        $return = $this->createReturn();
        $item = $return->items()->where('item_id', $this->monitor->id)->where('item_type', Asset::class)->firstOrFail();

        $this->actingAs($this->admin)->post(route('returns.items.store', [$return, $item]))->assertSessionHasNoErrors();

        $this->monitor->refresh();
        $this->assertNull($this->monitor->assigned_to);
        $this->assertSame($this->storage->id, (int) $this->monitor->location_id);
        $this->assertSame(trans('asset-returns.in_warehouse', ['place' => 'Midtown clinic']), AssetReturns::storageFor($this->monitor)['label']);
        $this->actingAs($this->admin)->get(route('hardware.show', $this->monitor))
            ->assertOk()
            ->assertSee(trans('asset-returns.in_warehouse', ['place' => 'Midtown clinic']));

        // Checked out again: it is no longer waiting in the warehouse.
        $this->monitor->checkOut($this->person, $this->admin, now()->addMinute()->format('Y-m-d H:i:s'));
        $this->assertNull(AssetReturns::storageFor($this->monitor->fresh()));

        $headsetItem = $return->items()->where('item_type', Accessory::class)->firstOrFail();
        $this->actingAs($this->admin)->post(route('returns.items.store', [$return, $headsetItem]))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('accessories_checkout', ['id' => $this->headsetCheckout->id]);
        $this->assertDatabaseMissing('accessories_checkout', ['accessory_id' => $this->headsetCheckout->accessory_id]);
    }

    public function test_partner_shipment_steps_check_the_item_in_and_need_setup_when_it_was_not_with_the_partner(): void
    {
        $warehouse = Location::factory()->create(['is_storage' => true]);
        $this->settings->set(['return_partner_location_id' => $warehouse->id]);
        $return = $this->createReturn();

        foreach (range(1, 4) as $step) {
            $this->actingAs($this->admin)->post(route('returns.advance', $return))->assertSessionHasNoErrors();
        }

        $return->refresh();
        $this->assertNotNull($return->warehouse_arrived_at);
        $this->assertNull($this->laptop->fresh()->assigned_to);
        $this->assertSame($warehouse->id, (int) $this->laptop->fresh()->location_id);
        $this->assertSame(trans('asset-returns.in_warehouse', ['place' => 'Rippling']), AssetReturns::storageFor($this->laptop->fresh())['label']);
        $this->assertNull($return->completed_at);

        $item = $return->items()->where('item_id', $this->laptop->id)->where('item_type', Asset::class)->firstOrFail();
        $this->actingAs($this->admin)->post(route('returns.items.advance', [$return, $item]))->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('returns.items.advance', [$return, $item]))->assertSessionHasNoErrors();
        $this->assertNotNull($item->fresh()->partner_configured_at);

        foreach ($return->items()->where('destination', 'storage')->get() as $storageItem) {
            $this->actingAs($this->admin)->post(route('returns.items.store', [$return, $storageItem]));
        }

        $this->assertNotNull($return->fresh()->completed_at);
    }

    public function test_each_destination_asks_what_it_needs(): void
    {
        $this->actingAs($this->admin)->post(route('returns.store'), [
            'user_id' => $this->person->id,
            'reason' => 'change',
            'items' => ['asset-'.$this->laptop->id => ['include' => 1, 'destination' => 'storage', 'location_id' => 0]],
        ])->assertSessionHasErrors();

        $this->actingAs($this->admin)->post(route('returns.store'), [
            'user_id' => $this->person->id,
            'reason' => 'change',
            'items' => ['asset-'.$this->laptop->id => ['include' => 1, 'destination' => 'partner']],
        ])->assertSessionHasErrors();

        $this->assertSame(0, AssetReturn::count());
    }

    public function test_any_location_except_the_partner_warehouse_can_keep_items(): void
    {
        $warehouse = Location::factory()->create(['is_storage' => true]);
        $this->settings->set(['return_partner_location_id' => $warehouse->id]);

        $this->actingAs($this->admin)->get(route('returns.create', ['user_id' => $this->person->id]))
            ->assertOk()
            ->assertSee('Rippling')
            ->assertSee('Midtown clinic');

        $this->actingAs($this->admin)->post(route('returns.store'), [
            'user_id' => $this->person->id,
            'reason' => 'change',
            'items' => ['asset-'.$this->monitor->id => ['include' => 1, 'destination' => 'storage', 'location_id' => $warehouse->id]],
        ])->assertSessionHasErrors();
    }

    public function test_a_return_can_be_cancelled_until_something_is_checked_in(): void
    {
        $return = $this->createReturn();
        $this->actingAs($this->admin)->post(route('returns.cancel', $return))->assertSessionHasNoErrors();
        $this->assertNotNull($return->fresh()->cancelled_at);
        $this->assertSame($this->person->id, (int) $this->laptop->fresh()->assigned_to);

        $second = $this->createReturn();
        $item = $second->items()->where('destination', 'storage')->firstOrFail();
        $this->actingAs($this->admin)->post(route('returns.items.store', [$second, $item]));
        $this->actingAs($this->admin)->post(route('returns.cancel', $second))->assertSessionHasErrors('return');
    }

    public function test_only_people_who_can_check_in_manage_returns(): void
    {
        $this->actingAs(User::factory()->viewAssets()->create())->get(route('returns.create'))->assertForbidden();
        $this->actingAs(User::factory()->viewAssets()->create())->get(route('returns.index'))->assertOk();
    }

    public function test_any_shipping_step_can_be_recorded_by_hand_with_its_date_and_undone(): void
    {
        $return = $this->createReturn();
        $this->travel(3)->days();
        $day = $return->created_at->copy()->addDay()->toDateString();

        // The person already shipped: the earlier steps take the same date.
        $this->actingAs($this->admin)->post(route('returns.advance', $return), ['step' => 'boxes_shipped_at', 'date' => $day])
            ->assertSessionHasNoErrors();
        $return->refresh();
        $this->assertSame($day, $return->address_confirmed_at->toDateString());
        $this->assertSame($day, $return->boxes_sent_at->toDateString());
        $this->assertSame($day, $return->boxes_shipped_at->toDateString());
        $this->assertNull($return->warehouse_arrived_at);

        $this->actingAs($this->admin)->post(route('returns.undo', $return))->assertSessionHasNoErrors();
        $this->assertNull($return->fresh()->boxes_shipped_at);
        $this->assertNotNull($return->fresh()->boxes_sent_at);

        // Dates in the future are refused.
        $this->actingAs($this->admin)->post(route('returns.advance', $return), ['step' => 'boxes_shipped_at', 'date' => now()->addDay()->toDateString()])
            ->assertSessionHasErrors('step');

        // The arrival checks items in, so it cannot be undone.
        $this->actingAs($this->admin)->post(route('returns.advance', $return), ['step' => 'warehouse_arrived_at'])->assertSessionHasNoErrors();
        $this->assertNull($this->laptop->fresh()->assigned_to);
        $this->actingAs($this->admin)->post(route('returns.undo', $return))->assertSessionHasErrors('step');
    }

    public function test_partner_steps_are_recorded_by_hand_and_undoing_one_reopens_the_return(): void
    {
        $return = $this->createReturn();
        $this->actingAs($this->admin)->post(route('returns.advance', $return), ['step' => 'warehouse_arrived_at']);
        foreach ($return->items()->where('destination', 'storage')->get() as $storageItem) {
            $this->actingAs($this->admin)->post(route('returns.items.store', [$return, $storageItem]));
        }

        $laptop = $return->items()->where('item_id', $this->laptop->id)->where('item_type', Asset::class)->firstOrFail();
        $this->actingAs($this->admin)->post(route('returns.items.advance', [$return, $laptop]), ['step' => 'partner_configured_at'])
            ->assertSessionHasNoErrors();
        $this->assertNotNull($laptop->fresh()->partner_received_at);
        $this->assertNotNull($return->fresh()->completed_at);

        $this->actingAs($this->admin)->post(route('returns.items.undo', [$return, $laptop]))->assertSessionHasNoErrors();
        $this->assertNull($laptop->fresh()->partner_configured_at);
        $this->assertNull($return->fresh()->completed_at);
    }

    public function test_return_details_can_be_corrected(): void
    {
        $return = $this->createReturn();

        $this->actingAs($this->admin)->put(route('returns.update', $return), [
            'reason' => 'change',
            'ticket_number' => 'INC-9999',
            'carrier' => 'FedEx',
            'tracking_number' => '7712 3456 7890',
        ])->assertSessionHasNoErrors();

        $return->refresh();
        $this->assertSame('change', $return->reason);
        $this->assertSame('INC-9999', $return->ticket_number);
        $this->assertSame('7712 3456 7890', $return->tracking_number);
        $this->actingAs($this->admin)->get(route('returns.show', $return))->assertSee('FedEx');
    }

    public function test_only_the_last_level_of_locations_can_keep_items(): void
    {
        $country = Location::factory()->create(['name' => 'United States']);
        $state = Location::factory()->create(['name' => 'New Jersey', 'parent_id' => $country->id]);
        $clinic = Location::factory()->create(['name' => 'Edgewater clinic', 'parent_id' => $state->id]);
        // A state with no clinics yet is not a clinic either.
        $emptyState = Location::factory()->create(['name' => 'California', 'parent_id' => $country->id]);

        $tree = AssetReturns::clinicLocationTree()->keyBy(fn (array $row) => $row['location']->name);
        $this->assertFalse($tree['United States']['selectable']);
        $this->assertFalse($tree['New Jersey']['selectable']);
        $this->assertFalse($tree['California']['selectable']);
        $this->assertTrue($tree['Edgewater clinic']['selectable']);
        $this->assertSame(2, $tree['Edgewater clinic']['depth']);

        foreach ([$country, $state, $emptyState] as $notAClinic) {
            $this->actingAs($this->admin)->post(route('returns.store'), [
                'user_id' => $this->person->id,
                'reason' => 'change',
                'items' => ['asset-'.$this->monitor->id => ['include' => 1, 'destination' => 'storage', 'location_id' => $notAClinic->id]],
            ])->assertSessionHasErrors();
        }

        $this->actingAs($this->admin)->post(route('returns.store'), [
            'user_id' => $this->person->id,
            'reason' => 'change',
            'items' => ['asset-'.$this->monitor->id => ['include' => 1, 'destination' => 'storage', 'location_id' => $clinic->id]],
        ])->assertSessionHasNoErrors();
    }
}
