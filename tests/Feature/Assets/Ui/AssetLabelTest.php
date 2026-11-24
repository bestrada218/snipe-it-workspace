<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\User;
use Tests\TestCase;

class AssetLabelTest extends TestCase
{
    public function test_user_with_permissions_can_access_page()
    {
        $assets = Asset::factory()->count(20)->create();
        $id_array = $assets->pluck('id')->toArray();

        $this->actingAs(User::factory()->viewAssets()->create())->post('/hardware/bulkedit', [
            'ids' => $id_array,
            'bulk_actions' => 'labels',
        ])->assertStatus(200);
    }

    public function test_redirect_of_no_assets_selected()
    {
        $id_array = [];
        $this->actingAs(User::factory()->viewAssets()->create())
            ->from(route('hardware.index'))
            ->post('/hardware/bulkedit', [
                'ids' => $id_array,
                'bulk_actions' => 'Labels',
            ])->assertStatus(302)
            ->assertRedirect(route('hardware.index'));
    }

    public function test_classic_labels_print_the_tag_first_and_the_company(): void
    {
        $this->settings->set([
            'label2_enable' => 0,
            'site_name' => 'Acme IT',
            'labels_display_tag' => 1,
            'labels_display_name' => 1,
            'labels_display_company_name' => 1,
        ]);
        $asset = Asset::factory()->create(['asset_tag' => 'NY-001-LP-W11', 'name' => 'Reception laptop', 'company_id' => null]);

        $this->actingAs(User::factory()->viewAssets()->create())->post('/hardware/bulkedit', [
            'ids' => [$asset->id],
            'bulk_actions' => 'labels',
        ])->assertOk()
            ->assertSeeInOrder(['label-tag', 'NY-001-LP-W11', 'N: Reception laptop', 'C: Acme IT'], false);
    }
}
