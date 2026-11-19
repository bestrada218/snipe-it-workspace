<?php

namespace Tests\Feature\AssetShipments;

use App\Enums\ActionType;
use App\Enums\FileStorage;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\AssetShipment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetShipmentFlowTest extends TestCase
{
    private User $approver;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        $this->approver = User::factory()->create();
        $this->settings->set(['purchase_approvers' => [$this->approver->id]]);
    }

    public function test_index_page_renders(): void
    {
        AssetShipment::factory()->create();

        $this->actingAs(User::factory()->viewAssets()->checkoutAssets()->create())
            ->get(route('shipments.index'))
            ->assertOk();
    }

    public function test_shipment_detail_requires_permission(): void
    {
        $shipment = AssetShipment::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('shipments.show', $shipment))
            ->assertForbidden();
    }

    public function test_recipient_can_view_their_shipment(): void
    {
        $shipment = AssetShipment::factory()->create();

        $this->actingAs($shipment->recipient)
            ->get(route('shipments.show', $shipment))
            ->assertOk();
    }

    public function test_registering_a_shipment_reserves_the_asset(): void
    {
        $asset = Asset::factory()->create();
        $recipient = User::factory()->create();

        $this->actingAs(User::factory()->viewAssets()->checkoutAssets()->create())
            ->post(route('shipments.store', $asset), $this->shipmentPayload($recipient))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $shipment = AssetShipment::where('asset_id', $asset->id)->sole();
        $this->assertSame($recipient->id, (int) $shipment->recipient_id);
        $this->assertSame($this->approver->display_name, $shipment->purchase_approver_name);
        Storage::assertExists(FileStorage::AssetShipmentApprovals->privateStorageKey().$shipment->approval_key);
        $this->assertFalse($asset->fresh()->availableForCheckout());
        $this->assertTrue(Actionlog::where('item_id', $asset->id)->where('action_type', ActionType::ShipmentCreated->value)->exists());
    }

    public function test_registering_requires_an_authorized_purchase_approver(): void
    {
        $asset = Asset::factory()->create();
        $payload = ['purchase_approver_id' => User::factory()->create()->id] + $this->shipmentPayload(User::factory()->create());

        $this->actingAs(User::factory()->viewAssets()->checkoutAssets()->create())
            ->post(route('shipments.store', $asset), $payload)
            ->assertSessionHasErrors('purchase_approver_id');

        $this->assertFalse(AssetShipment::where('asset_id', $asset->id)->exists());
    }

    public function test_registering_requires_checkout_permission(): void
    {
        $asset = Asset::factory()->create();

        $this->actingAs(User::factory()->viewAssets()->create())
            ->post(route('shipments.store', $asset), $this->shipmentPayload(User::factory()->create()))
            ->assertForbidden();
    }

    public function test_recipient_confirming_receipt_assigns_the_asset(): void
    {
        $shipment = AssetShipment::factory()->create();

        $this->actingAs($shipment->recipient)
            ->post(route('shipments.receive', $shipment))
            ->assertRedirect();

        $shipment->refresh();
        $this->assertNotNull($shipment->received_at);
        $this->assertNotNull($shipment->assigned_at);
        $asset = $shipment->asset->fresh();
        $this->assertSame(User::class, $asset->assigned_type);
        $this->assertSame($shipment->recipient_id, (int) $asset->assigned_to);
    }

    public function test_staff_must_explain_how_receipt_was_confirmed(): void
    {
        $shipment = AssetShipment::factory()->create();

        $this->actingAs(User::factory()->viewAssets()->checkoutAssets()->create())
            ->post(route('shipments.receive', $shipment))
            ->assertSessionHasErrors('receipt_note');

        $this->assertNull($shipment->fresh()->received_at);
    }

    public function test_other_people_cannot_confirm_receipt(): void
    {
        $shipment = AssetShipment::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('shipments.receive', $shipment), ['receipt_note' => 'Llamada'])
            ->assertForbidden();

        $this->assertNull($shipment->fresh()->received_at);
    }

    public function test_cancelling_releases_the_asset(): void
    {
        $shipment = AssetShipment::factory()->create();

        $this->actingAs(User::factory()->viewAssets()->checkoutAssets()->create())
            ->post(route('shipments.cancel', $shipment), ['reason' => 'Pedido devuelto'])
            ->assertRedirect();

        $this->assertNotNull($shipment->fresh()->cancelled_at);
        $this->assertTrue($shipment->asset->fresh()->availableForCheckout());
    }

    public function test_recipient_cannot_cancel(): void
    {
        $shipment = AssetShipment::factory()->create();

        $this->actingAs($shipment->recipient)
            ->post(route('shipments.cancel', $shipment), ['reason' => 'Ya no lo necesito'])
            ->assertForbidden();

        $this->assertNull($shipment->fresh()->cancelled_at);
    }

    /**
     * @return array<string, mixed>
     */
    private function shipmentPayload(User $recipient): array
    {
        return [
            'recipient_id' => $recipient->id,
            'platform' => 'Rippling',
            'carrier' => 'Servientrega',
            'tracking_number' => '9988776655',
            'expected_on' => now()->addWeek()->toDateString(),
            'purchase_approver_id' => $this->approver->id,
            'approval_reference' => 'Aprobado por correo el 18 de noviembre',
            'approval' => UploadedFile::fake()->create('aprobacion.pdf', 20, 'application/pdf'),
        ];
    }
}
