<?php

namespace Tests\Feature\Dashboard;

use App\Models\Asset;
use App\Models\AssetShipment;
use App\Models\Maintenance;
use App\Models\User;
use App\Services\AssetReplacement;
use Tests\TestCase;

class NeedsAttentionTest extends TestCase
{
    public function test_replacement_counts_match_the_individual_windows(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        foreach ([-2, 0, 7, 20, 45, 110] as $days) {
            Asset::factory()->create(['replacement_due_on' => today()->addDays($days)->toDateString()]);
        }

        $expected = array_map(fn (array $window): int => AssetReplacement::query($window)->count(), AssetReplacement::windows());

        $this->assertSame($expected, AssetReplacement::windowCounts());
    }

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

    public function test_lists_other_reminders_in_the_same_panel(): void
    {
        Maintenance::factory()->create([
            'expected_completion_date' => now()->subDays(2)->format('Y-m-d H:i:s'),
            'completed_at' => null,
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee(trans_choice('dashboard-attention.maintenances_overdue', 1))
            ->assertDontSee('wire:name="needs-attention"', false);
    }
}
