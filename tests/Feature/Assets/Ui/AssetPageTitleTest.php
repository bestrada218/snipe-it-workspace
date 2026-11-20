<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\User;
use Tests\TestCase;

class AssetPageTitleTest extends TestCase
{
    public function test_asset_page_title_leads_with_the_tag(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'NY-042-LP', 'name' => 'Reception laptop']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSee('NY-042-LP · Reception laptop');
    }

    public function test_asset_without_a_name_uses_the_model(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'NY-043-LP', 'name' => null]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.edit', $asset))
            ->assertOk()
            ->assertSee('NY-043-LP · '.$asset->model->name);
    }
}
