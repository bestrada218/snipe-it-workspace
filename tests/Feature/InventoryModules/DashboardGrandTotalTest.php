<?php

namespace Tests\Feature\InventoryModules;

use App\Models\Accessory;
use App\Models\Consumable;
use App\Models\User;
use Tests\TestCase;

class DashboardGrandTotalTest extends TestCase
{
    public function test_disabled_modules_are_left_out_of_the_grand_total(): void
    {
        Accessory::factory()->create(['qty' => 7]);
        Consumable::factory()->create(['qty' => 10]);
        $this->settings->set(['disabled_inventory_modules' => ['consumables']]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('home'))
            ->assertViewHas('counts', function (array $counts): bool {
                $this->assertSame($counts['asset'] + $counts['accessory'] + $counts['license'], $counts['grand_total']);
                $this->assertSame(7, $counts['accessory']);

                return true;
            });
    }
}
