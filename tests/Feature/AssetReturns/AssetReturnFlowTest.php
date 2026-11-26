<?php

namespace Tests\Feature\AssetReturns;

use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Company;
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

        $this->settings->set(['return_partner_name' => 'Example Provider']);
        $this->person = User::factory()->create();
        $this->admin = User::factory()->superuser()->create();
        $this->laptop = Asset::factory()->assignedToUser($this->person)->create(['asset_tag' => 'NY-301-LP-W11']);
        $this->monitor = Asset::factory()->assignedToUser($this->person)->create(['asset_tag' => 'NY-302-TV']);
        $headset = Accessory::factory()->create(['qty' => 5, 'asset_tag' => 'NY-303-HS']);
        $this->headsetCheckout = AccessoryCheckout::create(['accessory_id' => $headset->id, 'assigned_to' => $this->person->id, 'assigned_type' => User::class]);
        $this->storage = Location::factory()->create(['name' => 'Main warehouse']);
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
            ->assertSee(trans('asset-returns.destinations.storage'))
            ->assertSee('NY-301-LP-W11')
            ->assertSee('NY-302-TV')
            ->assertSee('NY-303-HS');
    }

    public function test_company_scoped_staff_cannot_read_or_modify_another_companys_return(): void
    {
        $return = $this->createReturn();
        $other = Company::factory()->create();
        $allowed = Company::factory()->create();
        $this->person->companies()->attach($other);
        $viewer = User::factory()->admin()->create();
        $viewer->companies()->attach($allowed);
        $visible = AssetReturn::factory()->create(['user_id' => $viewer->id, 'ticket_number' => 'VISIBLE-TICKET']);
        $this->settings->set(['full_multiple_companies_support' => 1, 'null_company_is_floater' => 0]);

        $this->actingAs($viewer)->get(route('returns.index'))->assertOk()->assertDontSee('INC-4321');
        $this->get(route('returns.show', $visible))->assertOk()->assertSee('VISIBLE-TICKET');
        $this->get(route('returns.show', $return))->assertNotFound();
        $this->post(route('returns.notes', $return), ['note' => 'Out of scope'])->assertNotFound();
        $this->post(route('returns.store'), [
            'user_id' => $this->person->id, 'reason' => 'termination',
            'items' => [['include' => 1]],
        ])->assertSessionHasErrors('user_id');
    }

    public function test_a_return_containing_accessories_requires_their_own_permissions(): void
    {
        $assetOperator = User::factory()->viewAssets()->checkinAssets()->create();
        $this->actingAs($assetOperator)->get(route('returns.create', ['user_id' => $this->person->id]))
            ->assertOk()->assertSee('NY-301-LP-W11')->assertDontSee('NY-303-HS');

        $return = $this->createReturn();
        $this->actingAs($assetOperator)->get(route('returns.show', $return))->assertNotFound();

        $readOnlyAccessories = User::factory()->viewAssets()->checkinAssets()->viewAccessories()->create();
        $this->assertTrue($readOnlyAccessories->cannot('checkin', Accessory::class));
        $response = $this->actingAs($readOnlyAccessories)->get(route('returns.show', $return))
            ->assertOk()->assertViewHas('canUpdate', false);
        $this->assertTrue(str_contains($response->getContent(), 'NY-303-HS'));
        $this->assertFalse(str_contains($response->getContent(), trans('asset-returns.edit_details')));
        $this->post(route('returns.notes', $return), ['note' => 'Restricted'])->assertForbidden();
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

    public function test_storing_an_item_leaves_it_available_in_the_internal_warehouse(): void
    {
        $return = $this->createReturn();
        $item = $return->items()->where('item_id', $this->monitor->id)->where('item_type', Asset::class)->firstOrFail();

        $this->actingAs($this->admin)->post(route('returns.items.store', [$return, $item]))->assertSessionHasNoErrors();

        $this->monitor->refresh();
        $this->assertNull($this->monitor->assigned_to);
        $this->assertSame($this->storage->id, (int) $this->monitor->location_id);
        $this->assertSame('review', $this->monitor->status->workflow_key);
        $this->assertSame(trans('asset-returns.in_warehouse', ['place' => 'Main warehouse']), AssetReturns::storageFor($this->monitor)['label']);
        $this->actingAs($this->admin)->get(route('hardware.show', $this->monitor))
            ->assertOk()
            ->assertSee(trans('asset-returns.in_warehouse', ['place' => 'Main warehouse']));

        // Checked out again: it is no longer waiting in the warehouse.
        $this->monitor->checkOut($this->person, $this->admin, now()->addMinute()->format('Y-m-d H:i:s'));
        $this->assertNull(AssetReturns::storageFor($this->monitor->fresh()));

        $headsetItem = $return->items()->where('item_type', Accessory::class)->firstOrFail();
        $this->actingAs($this->admin)->post(route('returns.items.store', [$return, $headsetItem]))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('accessories_checkout', ['id' => $this->headsetCheckout->id]);
        $this->assertDatabaseMissing('accessories_checkout', ['accessory_id' => $this->headsetCheckout->accessory_id]);
        $this->assertDatabaseHas('accessory_stock_locations', [
            'accessory_id' => $this->headsetCheckout->accessory_id,
            'location_id' => $this->storage->id,
        ]);
    }

    public function test_accessory_checkout_consumes_a_unit_from_the_selected_warehouse(): void
    {
        $accessory = $this->headsetCheckout->accessory;
        $secondWarehouse = Location::factory()->create(['name' => 'Secondary warehouse']);
        $return = $this->createReturn();
        $item = $return->items()->where('item_type', Accessory::class)->firstOrFail();
        $this->actingAs($this->admin)->post(route('returns.items.store', [$return, $item]))->assertSessionHasNoErrors();

        $another = AccessoryCheckout::create([
            'accessory_id' => $accessory->id,
            'assigned_to' => $this->person->id,
            'assigned_type' => User::class,
        ]);
        $secondReturn = $this->createReturn(['items' => [
            'accessory-'.$another->id => ['include' => 1, 'destination' => 'storage', 'location_id' => $secondWarehouse->id],
            'accessory-'.$this->headsetCheckout->id => ['include' => 0],
            'asset-'.$this->laptop->id => ['include' => 0],
            'asset-'.$this->monitor->id => ['include' => 0],
        ]]);
        $this->post(route('returns.items.store', [$secondReturn, $secondReturn->items()->sole()]))->assertSessionHasNoErrors();

        $this->get(route('accessories.show', $accessory))->assertOk()
            ->assertSee($this->storage->name)->assertSee($secondWarehouse->name);
        $this->get(route('accessories.checkout.show', $accessory))->assertOk()
            ->assertSee($this->storage->name)->assertSee($secondWarehouse->name);
        $recipient = User::factory()->create();
        $this->post(route('accessories.checkout.store', $accessory), [
            'checkout_to_type' => 'user',
            'assigned_user' => $recipient->id,
            'checkout_qty' => 1,
            'stock_location_id' => $secondWarehouse->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('accessory_stock_locations', ['accessory_id' => $accessory->id, 'location_id' => $this->storage->id]);
        $this->assertDatabaseMissing('accessory_stock_locations', ['accessory_id' => $accessory->id, 'location_id' => $secondWarehouse->id]);

        $this->post(route('accessories.checkout.store', $accessory), [
            'checkout_to_type' => 'user',
            'assigned_user' => User::factory()->create()->id,
            'checkout_qty' => 1,
            'stock_location_id' => $secondWarehouse->id,
        ])->assertSessionHasErrors('stock_location_id');
        $this->assertSame(1, $accessory->checkouts()->count());
    }

    public function test_warehouse_can_be_changed_through_audit_and_bulk_edit(): void
    {
        $provider = Location::factory()->create(['is_storage' => true]);
        $this->settings->set(['return_partner_location_id' => $provider->id]);
        [$asset, $second] = Asset::factory()->count(2)->create();

        $this->actingAs($this->admin)->get(route('asset.audit.create', $asset))
            ->assertOk()->assertSee(trans('asset-returns.warehouse_edit'))->assertSee('Example Provider');
        $this->post(route('asset.audit.store', $asset), ['warehouse_location_id' => $provider->id])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'location_id' => $provider->id, 'warehouse_location_id' => $provider->id]);
        $this->assertDatabaseHas('action_logs', ['action_type' => 'audit', 'item_id' => $asset->id, 'location_id' => $provider->id]);

        $label = trans('asset-returns.destinations.partner', ['partner' => 'Example Provider']);
        $this->get(route('warehouse-inventory.index', ['q' => 'Example Provider']))
            ->assertOk()->assertSee($label)->assertSee($asset->asset_tag);
        $csv = $this->get(route('warehouse-inventory.index', ['location_id' => $provider->id, 'export' => 'csv']))
            ->assertOk()->assertDownload();
        $this->assertStringContainsString($label, $csv->streamedContent());

        $this->post(route('hardware/bulksave'), [
            'ids' => [$asset->id, $second->id],
            'warehouse_location_id' => $this->storage->id,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        foreach ([$asset, $second] as $changed) {
            $this->assertDatabaseHas('assets', ['id' => $changed->id, 'assigned_to' => null,
                'location_id' => $this->storage->id, 'warehouse_location_id' => $this->storage->id]);
        }

        $this->post(route('hardware.bulk-audit.store'), [
            'selected_assets' => [$asset->id, $second->id],
            'warehouse_location_id' => $provider->id,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('assets', ['id' => $second->id, 'location_id' => $provider->id, 'warehouse_location_id' => $provider->id]);

        $this->get(route('hardware.edit', $asset))->assertOk()->assertSee(trans('asset-returns.warehouse_edit'));
        $this->put(route('hardware.update', $asset), [
            'asset_tags' => $asset->asset_tag,
            'name' => $asset->name,
            'model_id' => $asset->model_id,
            'status_id' => $asset->status_id,
            'warehouse_location_id' => $this->storage->id,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'location_id' => $this->storage->id, 'warehouse_location_id' => $this->storage->id]);

        $this->post(route('hardware/bulksave'), [
            'ids' => [$asset->id, $this->monitor->id],
            'warehouse_location_id' => $this->storage->id,
        ])->assertSessionHasErrors('warehouse_location_id');
        $this->assertSame($this->storage->id, (int) $asset->fresh()->location_id);
    }

    public function test_direct_checkin_requires_an_internal_warehouse_and_shows_stock_in_the_selected_state(): void
    {
        $country = Location::factory()->create();
        $state = Location::factory()->create(['parent_id' => $country->id]);
        $warehouse = Location::factory()->create(['parent_id' => $state->id]);
        $otherState = Location::factory()->create(['parent_id' => $country->id]);
        $otherWarehouse = Location::factory()->create(['parent_id' => $otherState->id, 'is_storage' => true]);
        $otherAsset = Asset::factory()->create(['location_id' => $otherWarehouse->id, 'warehouse_location_id' => $otherWarehouse->id]);

        $this->actingAs($this->admin)->get(route('hardware.checkin.create', $this->monitor))
            ->assertOk()
            ->assertSee(route('returns.create', ['user_id' => $this->person->id, 'asset_id' => $this->monitor->id]));
        $this->get(route('returns.create', ['user_id' => $this->person->id, 'asset_id' => $this->monitor->id]))
            ->assertOk()->assertViewHas('requestedAssetId', $this->monitor->id);

        $url = route('hardware.checkin.store', $this->monitor);
        foreach ([null, $state->id] as $invalid) {
            $this->post($url, ['warehouse_checkin' => 1, 'warehouse_location_id' => $invalid])
                ->assertSessionHasErrors('warehouse_location_id');
            $this->assertSame($this->person->id, (int) $this->monitor->fresh()->assigned_to);
        }

        $this->post($url, ['warehouse_checkin' => 1, 'warehouse_location_id' => $warehouse->id, 'name' => $this->monitor->name])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('assets', ['id' => $this->monitor->id, 'assigned_to' => null,
            'location_id' => $warehouse->id, 'warehouse_location_id' => $warehouse->id]);
        $response = $this->get(route('warehouse-inventory.index', ['state_id' => $state->id, 'location_id' => $otherWarehouse->id]))
            ->assertOk()->assertSee($this->monitor->asset_tag)->assertDontSee($otherAsset->asset_tag);
        $response->assertSee('<option value="'.$warehouse->id.'"', false)
            ->assertDontSee('<option value="'.$otherWarehouse->id.'"', false);

        $this->monitor->refresh()->checkOut($this->person, $this->admin, now()->format('Y-m-d H:i:s'));
        $this->assertNull($this->monitor->fresh()->warehouse_location_id);
    }

    public function test_existing_provider_without_location_is_linked_by_the_migration(): void
    {
        $this->settings->set(['return_partner_location_id' => null]);
        $migration = require database_path('migrations/2026_11_25_160000_link_existing_external_warehouse.php');
        $migration->up();

        $location = Location::where('name', 'Example Provider')->sole();
        $this->assertTrue($location->is_external);
        $this->assertNull($location->parent_id);
        $this->assertDatabaseHas('settings', ['return_partner_location_id' => $location->id]);

        $migration->up();
        $this->assertSame(1, Location::where('name', 'Example Provider')->count());
    }

    public function test_saving_a_provider_name_registers_a_selectable_external_warehouse(): void
    {
        $this->actingAs($this->admin);
        $settings = [
            'return_settings_configured' => 1,
            'return_partner_name' => 'Example Provider',
            'return_partner_location_id' => '',
            'thumbnail_max_h' => 50,
            'per_page' => 50,
        ];

        $this->post(route('settings.general.save'), $settings)->assertSessionHasNoErrors()->assertSessionHas('success');
        $location = AssetReturns::partnerLocation();
        $this->assertNotNull($location);
        $this->assertTrue($location->is_external);
        $this->assertTrue($location->is_storage);
        $this->assertNull($location->parent_id);

        $this->post(route('settings.general.save'), $settings)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame($location->id, AssetReturns::partnerLocation()?->id);
        $this->get(route('warehouse-inventory.index', ['ownership' => 'external']))
            ->assertOk()->assertSee('<option value="'.$location->id.'"', false);
    }

    public function test_warehouse_ownership_filters_include_each_registered_external_location(): void
    {
        $own = Location::factory()->create(['is_storage' => true]);
        $partner = Location::factory()->create(['is_storage' => true]);
        $external = Location::factory()->create(['is_storage' => true, 'is_external' => true]);
        $this->settings->set(['return_partner_location_id' => $partner->id]);

        $ownAsset = Asset::factory()->create(['location_id' => $own->id]);
        $partnerAsset = Asset::factory()->create(['location_id' => $partner->id]);
        $externalAsset = Asset::factory()->create(['location_id' => $external->id]);

        $this->actingAs($this->admin)->get(route('warehouse-inventory.index', ['ownership' => 'own']))
            ->assertOk()->assertSee($ownAsset->asset_tag)->assertDontSee($partnerAsset->asset_tag)->assertDontSee($externalAsset->asset_tag);

        $this->get(route('warehouse-inventory.index', ['ownership' => 'external', 'state_id' => $own->id]))
            ->assertOk()->assertSee($partnerAsset->asset_tag)->assertSee($externalAsset->asset_tag)->assertDontSee($ownAsset->asset_tag);

        $report = $this->get(route('warehouse-inventory.index', ['ownership' => 'external', 'location_id' => $external->id, 'export' => 'csv']))
            ->assertOk()->assertDownload()->streamedContent();
        $this->assertStringContainsString($externalAsset->asset_tag, $report);
        $this->assertStringNotContainsString($partnerAsset->asset_tag, $report);
    }

    public function test_warehouse_inventory_lists_stored_items_and_exports_filtered_rows(): void
    {
        $return = $this->createReturn();
        foreach ($return->items()->where('destination', 'storage')->get() as $item) {
            $this->post(route('returns.items.store', [$return, $item]))->assertSessionHasNoErrors();
        }

        $url = route('warehouse-inventory.index');
        $this->get($url)->assertOk()
            ->assertSee($this->monitor->asset_tag)
            ->assertSee($this->headsetCheckout->accessory->asset_tag)
            ->assertSee($this->storage->name)
            ->assertDontSee($this->laptop->asset_tag);

        $report = $this->get(route('warehouse-inventory.index', [
            'location_id' => $this->storage->id,
            'type' => 'accessory',
            'export' => 'csv',
        ]))->assertOk()->assertDownload();
        $this->assertStringContainsString($this->headsetCheckout->accessory->asset_tag, $report->streamedContent());
        $this->assertStringNotContainsString($this->monitor->asset_tag, $report->streamedContent());

        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
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
        $this->assertSame('setup', $this->laptop->fresh()->status->workflow_key);
        $this->assertSame('pending', $this->laptop->fresh()->status->getStatuslabelType());
        $this->get(route('warehouse-inventory.index', ['ownership' => 'external', 'location_id' => $warehouse->id]))
            ->assertOk()->assertSee($this->laptop->asset_tag);
        $this->assertSame(trans('asset-returns.in_warehouse', ['place' => 'Example Provider']), AssetReturns::storageFor($this->laptop->fresh())['label']);
        $this->assertNull($return->completed_at);

        $item = $return->items()->where('item_id', $this->laptop->id)->where('item_type', Asset::class)->firstOrFail();
        $this->actingAs($this->admin)->post(route('returns.items.advance', [$return, $item]))->assertSessionHasNoErrors();
        $this->assertSame('setup', $this->laptop->fresh()->status->workflow_key);
        $this->actingAs($this->admin)->post(route('returns.items.advance', [$return, $item]))->assertSessionHasNoErrors();
        $this->assertNotNull($item->fresh()->partner_configured_at);
        $this->assertSame('ready', $this->laptop->fresh()->status->workflow_key);

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
            ->assertSee('Example Provider')
            ->assertSee('Main warehouse');

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
        $warehouse = Location::factory()->create(['name' => 'Regional warehouse', 'parent_id' => $state->id]);
        // An empty branch is ineligible when another branch goes deeper.
        $emptyState = Location::factory()->create(['name' => 'California', 'parent_id' => $country->id]);

        $tree = AssetReturns::ownWarehouseTree()->keyBy(fn (array $row) => $row['location']->name);
        $this->assertFalse($tree['United States']['selectable']);
        $this->assertFalse($tree['New Jersey']['selectable']);
        $this->assertFalse($tree['California']['selectable']);
        $this->assertTrue($tree['Regional warehouse']['selectable']);
        $this->assertSame(2, $tree['Regional warehouse']['depth']);

        foreach ([$country, $state, $emptyState] as $ineligibleLocation) {
            $this->actingAs($this->admin)->post(route('returns.store'), [
                'user_id' => $this->person->id,
                'reason' => 'change',
                'items' => ['asset-'.$this->monitor->id => ['include' => 1, 'destination' => 'storage', 'location_id' => $ineligibleLocation->id]],
            ])->assertSessionHasErrors();
        }

        $this->actingAs($this->admin)->post(route('returns.store'), [
            'user_id' => $this->person->id,
            'reason' => 'change',
            'items' => ['asset-'.$this->monitor->id => ['include' => 1, 'destination' => 'storage', 'location_id' => $warehouse->id]],
        ])->assertSessionHasNoErrors();
    }
}
