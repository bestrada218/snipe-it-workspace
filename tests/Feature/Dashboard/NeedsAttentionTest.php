<?php

namespace Tests\Feature\Dashboard;

use App\Models\Asset;
use App\Models\AssetShipment;
use App\Models\User;
use Tests\TestCase;

class NeedsAttentionTest extends TestCase
{
    public function test_shows_what_needs_action(): void
    {
        Asset::factory()->create(['replacement_due_on' => now()->addDays(5)->toDateString()]);
        AssetShipment::factory()->create(['expected_on' => now()->subDay()->toDateString()]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee(trans('dashboard-attention.title'))
            ->assertSee(trans_choice('dashboard-attention.replacement_soon', 1))
            ->assertSee(trans_choice('dashboard-attention.shipments_due', 1));
    }

    public function test_says_all_clear_when_nothing_is_pending(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee(trans('dashboard-attention.all_clear'));
    }

    public function test_overdue_replacement_comes_first(): void
    {
        Asset::factory()->create(['replacement_due_on' => now()->subDays(3)->toDateString()]);
        Asset::factory()->create(['replacement_due_on' => now()->addDays(5)->toDateString()]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder([
                trans_choice('dashboard-attention.replacement_overdue', 1),
                trans_choice('dashboard-attention.replacement_soon', 1),
            ]);
    }
}
