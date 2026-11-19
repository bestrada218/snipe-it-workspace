<?php

namespace Tests\Feature\DuplicateAssignments;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DuplicateAssignmentRulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
    }

    public function test_preview_says_whether_the_category_requires_approval(): void
    {
        $laptops = AssetModel::factory()->create();
        $headsets = AssetModel::factory()->create();
        $this->settings->set(['duplicate_approval_categories' => [$laptops->category_id]]);
        $person = User::factory()->create();
        Asset::factory()->for($laptops, 'model')->assignedToUser($person)->create();
        Asset::factory()->for($headsets, 'model')->assignedToUser($person)->create();
        $actor = User::factory()->superuser()->create();

        $this->actingAs($actor)
            ->getJson($this->previewUrl(Asset::factory()->for($laptops, 'model')->create(), $person))
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('requires_approval', true);

        $this->actingAs($actor)
            ->getJson($this->previewUrl(Asset::factory()->for($headsets, 'model')->create(), $person))
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('requires_approval', false);
    }

    public function test_duplicate_outside_approval_categories_only_needs_a_justification(): void
    {
        $headsets = AssetModel::factory()->create();
        $person = User::factory()->create();
        Asset::factory()->for($headsets, 'model')->assignedToUser($person)->create();
        $second = Asset::factory()->for($headsets, 'model')->create();
        $checkout = ['checkout_to_type' => 'user', 'assigned_user' => $person->id];
        $actor = User::factory()->superuser()->create();

        $this->actingAs($actor)
            ->post(route('hardware.checkout.store', $second), $checkout)
            ->assertSessionHasErrors('duplicate_assignment_reason');
        $this->assertNull($second->fresh()->assigned_to);

        $this->actingAs($actor)
            ->post(route('hardware.checkout.store', $second), $checkout + [
                'duplicate_assignment_confirmed' => '1',
                'duplicate_assignment_reason' => 'Backup headset for night shifts',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($person->id, (int) $second->fresh()->assigned_to);
        $meta = json_decode($second->log()->where('action_type', 'checkout')->sole()->log_meta, true);
        $this->assertSame('Backup headset for night shifts', $meta['_duplicate_assignment_reason']);
        $this->assertArrayNotHasKey('_duplicate_assignment_approval', $meta);

        $this->actingAs($actor)
            ->get(route('duplicate-assignments.index'))
            ->assertOk()
            ->assertSee('Backup headset for night shifts')
            ->assertSee(trans('duplicate-assignments.justification_only'));
    }

    public function test_duplicate_in_an_approval_category_still_needs_approver_and_evidence(): void
    {
        $laptops = AssetModel::factory()->create();
        $this->settings->set([
            'duplicate_approval_categories' => [$laptops->category_id],
            'duplicate_assignment_approvers' => [User::factory()->create()->id],
        ]);
        $person = User::factory()->create();
        Asset::factory()->for($laptops, 'model')->assignedToUser($person)->create();
        $second = Asset::factory()->for($laptops, 'model')->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('hardware.checkout.store', $second), [
                'checkout_to_type' => 'user',
                'assigned_user' => $person->id,
                'duplicate_assignment_confirmed' => '1',
                'duplicate_assignment_reason' => 'Backup laptop',
            ])
            ->assertSessionHasErrors(['duplicate_assignment_approver', 'duplicate_assignment_evidence']);

        $this->assertNull($second->fresh()->assigned_to);
    }

    public function test_several_accessory_units_only_need_a_justification(): void
    {
        $keyboards = Accessory::factory()->create(['qty' => 5]);
        $person = User::factory()->create();
        $checkout = ['checkout_to_type' => 'user', 'assigned_user' => $person->id, 'checkout_qty' => 2];
        $actor = User::factory()->superuser()->create();

        $this->actingAs($actor)
            ->post(route('accessories.checkout.store', $keyboards), $checkout)
            ->assertSessionHasErrors('duplicate_assignment_reason');

        $this->actingAs($actor)
            ->post(route('accessories.checkout.store', $keyboards), $checkout + [
                'duplicate_assignment_confirmed' => '1',
                'duplicate_assignment_reason' => 'One for the office and one for home',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $keyboards->checkouts()->where('assigned_to', $person->id)->count());
    }

    public function test_general_settings_save_the_approval_categories(): void
    {
        $laptops = Category::factory()->assetLaptopCategory()->create();
        $desktops = Category::factory()->assetDesktopCategory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('settings.general.index'))
            ->assertOk()
            ->assertSee(trans('duplicate-assignments.approval_categories'));

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('settings.general.save'), [
                'duplicate_approvers_configured' => '1',
                'duplicate_approval_categories' => [$laptops->id, $desktops->id],
                'thumbnail_max_h' => 50,
                'per_page' => 50,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([$laptops->id, $desktops->id], Setting::getSettings()->fresh()->duplicate_approval_categories);
    }

    private function previewUrl(Asset $asset, User $person): string
    {
        return route('duplicate-assignments.preview', ['type' => 'assets', 'id' => $asset->id, 'user' => $person->id, 'quantity' => 1]);
    }
}
