<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Statuslabel;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ApprovesDuplicateAssignments;
use Tests\TestCase;

class CreateAssetDuplicateAssignmentTest extends TestCase
{
    use ApprovesDuplicateAssignments;

    private AssetModel $laptops;

    private User $person;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        $this->laptops = AssetModel::factory()->create();
        $this->person = User::factory()->create();
        Asset::factory()->for($this->laptops, 'model')->assignedToUser($this->person)->create();
    }

    public function test_creating_and_assigning_a_second_unit_asks_for_a_justification(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('hardware.store'), $this->payload('NY-061-LP-W11'))
            ->assertSessionHasErrors('duplicate_assignment_reason');

        $this->assertFalse(Asset::where('asset_tag', 'NY-061-LP-W11')->exists());
    }

    public function test_a_justification_is_enough_outside_approval_categories(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('hardware.store'), $this->payload('NY-062-LP-W11') + [
                'duplicate_assignment_confirmed' => '1',
                'duplicate_assignment_reason' => 'Second laptop for the lab',
                'duplicate_assignment_ticket' => 'INC-1001',
            ])
            ->assertSessionHasNoErrors();

        $asset = Asset::where('asset_tag', 'NY-062-LP-W11')->sole();
        $this->assertSame($this->person->id, (int) $asset->assigned_to);
        $meta = json_decode($asset->log()->where('action_type', 'checkout')->sole()->log_meta, true);
        $this->assertSame('Second laptop for the lab', $meta['_duplicate_assignment_reason']);
    }

    public function test_approval_categories_also_need_approver_and_evidence_on_create(): void
    {
        $approval = $this->duplicateAssignmentApproval();
        $this->settings->set(['duplicate_approval_categories' => [$this->laptops->category_id]]);

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('hardware.store'), $this->payload('NY-063-LP-W11') + [
                'duplicate_assignment_confirmed' => '1',
                'duplicate_assignment_reason' => 'Second laptop for the lab',
                'duplicate_assignment_ticket' => 'INC-1001',
            ])
            ->assertSessionHasErrors(['duplicate_assignment_approver', 'duplicate_assignment_evidence']);
        $this->assertFalse(Asset::where('asset_tag', 'NY-063-LP-W11')->exists());

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('hardware.store'), $this->payload('NY-063-LP-W11') + $approval)
            ->assertSessionHasNoErrors();

        $asset = Asset::where('asset_tag', 'NY-063-LP-W11')->sole();
        $meta = json_decode($asset->log()->where('action_type', 'checkout')->sole()->log_meta, true);
        $this->assertArrayHasKey('_duplicate_assignment_approval', $meta);
    }

    public function test_preview_by_model_detects_the_duplicate(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->getJson(route('duplicate-assignments.preview-model', ['model' => $this->laptops->id, 'user' => $this->person->id, 'quantity' => 1]))
            ->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('existing', 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $tag): array
    {
        return [
            'asset_tags' => [1 => $tag],
            'model_id' => $this->laptops->id,
            'status_id' => Statuslabel::factory()->readyToDeploy()->create()->id,
            'checkout_to_type' => 'user',
            'assigned_user' => $this->person->id,
        ];
    }
}
