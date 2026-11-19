<?php

namespace Tests\Feature\Checkouts\Ui;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\User;
use Tests\TestCase;

/**
 * The asset tag identifies the unit being handed out, so every checkout form
 * leads with it, ahead of the item name.
 */
class CheckoutShowsAssetTagTest extends TestCase
{
    public function test_accessory_checkout_leads_with_the_asset_tag(): void
    {
        $accessory = Accessory::factory()->create(['asset_tag' => 'NY-020-HS', 'name' => 'Jabra Evolve2 40']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('accessories.checkout.show', $accessory))
            ->assertOk()
            ->assertSeeInOrder(['checkout-asset-tag-value', 'NY-020-HS', 'Jabra Evolve2 40'], false)
            ->assertDontSee('id="checkout_qty"', false);
    }

    public function test_accessory_without_a_tag_says_so(): void
    {
        $accessory = Accessory::factory()->create(['asset_tag' => null]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('accessories.checkout.show', $accessory))
            ->assertOk()
            ->assertSee(trans('asset-tags.missing_on_item'));
    }

    public function test_asset_checkout_leads_with_the_asset_tag(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'NY-021-LP-W11']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.checkout.create', $asset))
            ->assertOk()
            ->assertSeeInOrder(['checkout-asset-tag-value', 'NY-021-LP-W11'], false);
    }

    public function test_asset_picker_shows_the_tag_first(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'NY-022-DT-W11', 'name' => 'Reception desktop']);

        $text = collect($this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('assets.selectlist', ['search' => 'NY-022']))
            ->assertOk()
            ->json('results'))
            ->firstWhere('id', $asset->id)['text'];

        $this->assertStringStartsWith('NY-022-DT-W11 · Reception desktop', $text);
    }
}
