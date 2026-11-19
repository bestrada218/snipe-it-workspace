<?php

namespace Tests\Feature\InventoryModules;

use App\Models\User;
use Tests\TestCase;

class DisabledModuleNavigationTest extends TestCase
{
    public function test_disabled_modules_stay_out_of_the_menu_on_report_pages(): void
    {
        $this->settings->set(['disabled_inventory_modules' => ['licenses', 'consumables', 'components']]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('reports.activity'))
            ->assertOk()
            ->assertDontSee($this->menuLink('licenses.index'), false)
            ->assertDontSee($this->menuLink('consumables.index'), false)
            ->assertDontSee($this->menuLink('components.index'), false)
            ->assertSee($this->menuLink('accessories.index'), false);
    }

    public function test_disabled_module_routes_return_not_found(): void
    {
        $this->settings->set(['disabled_inventory_modules' => ['licenses']]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('licenses.index'))
            ->assertNotFound();
    }

    public function test_enabled_modules_appear_in_the_menu(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('reports.activity'))
            ->assertOk()
            ->assertSee($this->menuLink('licenses.index'), false)
            ->assertSee($this->menuLink('components.index'), false);
    }

    private function menuLink(string $route): string
    {
        return 'href="'.route($route).'"';
    }
}
