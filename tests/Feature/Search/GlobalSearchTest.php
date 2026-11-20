<?php

namespace Tests\Feature\Search;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\User;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    public function test_page_renders(): void
    {
        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('search'))
            ->assertOk()
            ->assertSee(trans('navigation.search_prompt'));
    }

    public function test_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('search', ['q' => 'NY']))
            ->assertForbidden();
    }

    public function test_exact_tag_opens_the_asset(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'NY-031-LP-W11']);

        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('search', ['q' => 'NY-031-LP-W11']))
            ->assertRedirect(route('hardware.show', $asset));
    }

    public function test_finds_assets_by_serial_and_lists_the_tag_first(): void
    {
        Asset::factory()->create(['asset_tag' => 'NY-032-DT-W11', 'serial' => 'SN-ABC-778899', 'name' => 'Reception desktop']);

        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('search', ['q' => 'ABC-7788']))
            ->assertOk()
            ->assertSeeInOrder(['NY-032-DT-W11', 'Reception desktop', 'SN-ABC-778899']);
    }

    public function test_finds_tagged_accessories(): void
    {
        Accessory::factory()->create(['asset_tag' => 'NY-033-HS', 'name' => 'Jabra Evolve2 40']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('search', ['q' => 'NY-033']))
            ->assertOk()
            ->assertSeeInOrder(['NY-033-HS', 'Jabra Evolve2 40']);
    }

    public function test_finds_people_by_full_name(): void
    {
        $person = User::factory()->create(['first_name' => 'Sofia', 'last_name' => 'Aguirre']);

        $this->actingAs(User::factory()->viewUsers()->create())
            ->get(route('search', ['q' => 'Sofia Agui']))
            ->assertOk()
            ->assertSee($person->username);
    }

    public function test_only_searches_what_the_person_may_see(): void
    {
        Asset::factory()->create(['asset_tag' => 'NY-034-LP-MAC']);

        $this->actingAs(User::factory()->viewUsers()->create())
            ->get(route('search', ['q' => 'NY-034']))
            ->assertOk()
            ->assertDontSee('NY-034-LP-MAC');
    }

    public function test_people_with_only_accessory_access_can_search_accessories(): void
    {
        Accessory::factory()->create(['asset_tag' => 'NY-036-HS', 'name' => 'Jabra Evolve2 40']);
        Asset::factory()->create(['asset_tag' => 'NY-037-LP-W11']);

        $this->actingAs(User::factory()->viewAccessories()->create())
            ->get(route('search', ['q' => 'NY-03']))
            ->assertOk()
            ->assertSee('NY-036-HS')
            ->assertDontSee('NY-037-LP-W11');
    }

    public function test_unnamed_assets_are_found_by_their_model(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'NY-038-LP-W11', 'name' => null]);

        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('search', ['q' => $asset->model->name]))
            ->assertOk()
            ->assertSee('NY-038-LP-W11');
    }

    public function test_more_results_can_be_opened(): void
    {
        foreach (range(40, 50) as $number) {
            Accessory::factory()->create(['asset_tag' => 'NJ-'.$number.'0-KB', 'name' => 'Logitech keyboard '.$number]);
        }
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->get(route('search', ['q' => 'Logitech keyboard']))
            ->assertOk()
            ->assertSee(trans('navigation.show_more'))
            ->assertDontSee('NJ-500-KB');

        $this->actingAs($user)
            ->get(route('search', ['q' => 'Logitech keyboard', 'more' => 'accessories']))
            ->assertOk()
            ->assertSee('NJ-500-KB');
    }

    public function test_disabled_modules_are_not_searched(): void
    {
        $this->settings->set(['disabled_inventory_modules' => ['accessories']]);
        Accessory::factory()->create(['asset_tag' => 'NY-035-KB']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('search', ['q' => 'NY-035']))
            ->assertOk()
            ->assertDontSee('NY-035-KB');
    }
}
