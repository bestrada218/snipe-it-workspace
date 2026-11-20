<?php

namespace Tests\Feature\DuplicateAssignments;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\PredefinedKit;
use App\Models\User;
use Tests\Support\ApprovesDuplicateAssignments;
use Tests\TestCase;

/**
 * Kits, bulk checkout and transfers between people have no
 * duplicate-assignment form, so they cannot hand a person a second unit of a
 * category they already hold: that goes through the individual checkout.
 */
class FlowsWithoutApprovalFormTest extends TestCase
{
    use ApprovesDuplicateAssignments;

    private AssetModel $laptops;

    private User $person;

    protected function setUp(): void
    {
        parent::setUp();

        $this->laptops = AssetModel::factory()->create();
        $this->person = User::factory()->create();
        Asset::factory()->for($this->laptops, 'model')->assignedToUser($this->person)->create();
    }

    public function test_bulk_checkout_refuses_a_second_unit_of_the_same_category(): void
    {
        $second = Asset::factory()->for($this->laptops, 'model')->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('hardware.bulkcheckout.store'), [
                'selected_assets' => [$second->id],
                'checkout_to_type' => 'user',
                'assigned_user' => $this->person->id,
            ])
            ->assertRedirect(route('hardware.bulkcheckout.show'))
            ->assertSessionHas('error', trans('duplicate-assignments.individual_checkout_required', [
                'person' => $this->person->display_name,
                'category' => $this->laptops->category->name,
            ]));

        $this->assertNull($second->fresh()->assigned_to);
    }

    public function test_bulk_checkout_with_the_approval_fields_is_allowed(): void
    {
        $second = Asset::factory()->for($this->laptops, 'model')->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('hardware.bulkcheckout.store'), [
                'selected_assets' => [$second->id],
                'checkout_to_type' => 'user',
                'assigned_user' => $this->person->id,
                'duplicate_assignment_confirmed' => 1,
                'duplicate_assignment_reason' => 'Loaner while the first one is repaired',
                'duplicate_assignment_ticket' => 'INC-2001',
            ])
            ->assertSessionMissing('error');

        $this->assertSame($this->person->id, (int) $second->fresh()->assigned_to);
    }

    public function test_kit_checkout_refuses_a_second_unit_and_assigns_nothing(): void
    {
        Asset::factory()->for($this->laptops, 'model')->create();
        $kit = PredefinedKit::factory()->create();
        $kit->models()->attach($this->laptops->id, ['quantity' => 1]);

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('kits.checkout.store', $kit), ['user_id' => $this->person->id])
            ->assertSessionMissing('success');

        $this->assertSame(1, Asset::where('assigned_to', $this->person->id)->count());
    }

    public function test_transfer_refuses_a_second_unit_for_the_target(): void
    {
        $source = User::factory()->create();
        $moving = Asset::factory()->for($this->laptops, 'model')->assignedToUser($source)->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->from(route('users.transfer.show', $source))
            ->post(route('users.transfer.store', $source), [
                'target_user_id' => $this->person->id,
                'asset_ids' => [$moving->id],
                'note' => 'Offboarding',
            ])
            ->assertSessionHas('error');

        $this->assertSame($source->id, (int) $moving->fresh()->assigned_to);
    }

    public function test_transfer_of_a_different_category_still_works(): void
    {
        $source = User::factory()->create();
        $monitor = Asset::factory()->assignedToUser($source)->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('users.transfer.store', $source), [
                'target_user_id' => $this->person->id,
                'asset_ids' => [$monitor->id],
                'note' => 'Offboarding',
            ])
            ->assertRedirect(route('users.show', $this->person));

        $this->assertSame($this->person->id, (int) $monitor->fresh()->assigned_to);
    }
}
