<?php

namespace Tests\Feature\AssetTags;

use App\Models\Asset;
use App\Models\User;
use Tests\TestCase;

class TagBuilderTest extends TestCase
{
    public function test_create_forms_offer_the_tag_builder_with_the_next_sequence(): void
    {
        Asset::factory()->create(['asset_tag' => 'NY-051-LP-W11']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('accessories.create'))
            ->assertOk()
            ->assertSee('data-tag-builder', false)
            ->assertSee('data-sequence="052"', false)
            ->assertSee(trans('asset-tags.types.HS'));
    }

    public function test_edit_forms_do_not_rebuild_an_existing_tag(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'NY-052-LP-W11']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.edit', $asset))
            ->assertOk()
            ->assertDontSee('data-tag-builder', false);
    }
}
