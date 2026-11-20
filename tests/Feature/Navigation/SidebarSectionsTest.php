<?php

namespace Tests\Feature\Navigation;

use App\Models\Asset;
use App\Models\AssetShipment;
use App\Models\User;
use Tests\TestCase;

class SidebarSectionsTest extends TestCase
{
    public function test_menu_is_grouped_into_sections(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder([
                trans('navigation.inventory'),
                trans('navigation.tracking'),
                trans('navigation.people'),
                trans('navigation.reports'),
                trans('navigation.administration'),
                trans('navigation.help'),
            ]);
    }

    public function test_sections_without_permitted_entries_are_hidden(): void
    {
        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('hardware.index'))
            ->assertOk()
            ->assertSee('<li class="header nav-section">'.trans('navigation.inventory').'</li>', false)
            ->assertDontSee('<li class="header nav-section">'.trans('navigation.administration').'</li>', false);
    }

    public function test_open_shipments_are_counted_in_the_menu(): void
    {
        AssetShipment::factory()->count(2)->create();

        $html = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('home'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/'.preg_quote(e(trans('asset-shipments.title')), '/').'<\/span>\s*<span class="pull-right-container">\s*<small class="label pull-right nav-count[^"]*">2<\/small>/',
            $html,
        );
    }

    public function test_asset_list_shows_status_filters_with_counts(): void
    {
        Asset::factory()->count(2)->create();

        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('hardware.index'))
            ->assertOk()
            ->assertSee('asset-status-chips', false)
            ->assertSee(route('hardware.index', ['status_type' => 'RTD']), false)
            ->assertViewHas('statusFilters', fn (array $filters) => $filters[0]['active'] && $filters[0]['count'] === 2);
    }

    public function test_status_filter_marks_the_selected_chip(): void
    {
        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('hardware.index', ['status_type' => 'Pending']))
            ->assertOk()
            ->assertViewHas('statusFilters', fn (array $filters) => collect($filters)
                ->where('active', true)->pluck('label')->all() === [trans('general.pending')]);
    }
}
