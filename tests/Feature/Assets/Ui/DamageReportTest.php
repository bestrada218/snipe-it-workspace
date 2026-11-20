<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Statuslabel;
use App\Models\User;
use Tests\TestCase;

class DamageReportTest extends TestCase
{
    private Statuslabel $damaged;

    protected function setUp(): void
    {
        parent::setUp();

        $this->damaged = Statuslabel::factory()->create(['name' => 'Damage']);
        $this->settings->set(['damage_report_statuses' => [$this->damaged->id]]);
    }

    public function test_moving_an_asset_to_a_damaged_status_requires_a_report(): void
    {
        $asset = Asset::factory()->create();
        $original = $asset->status_id;

        $this->actingAs(User::factory()->superuser()->create())
            ->put(route('hardware.update', $asset), $this->edit($asset))
            ->assertSessionHasErrors(['damage_description', 'damage_repairable', 'damage_repair_attempted']);

        $this->assertSame($original, $asset->fresh()->status_id);
    }

    public function test_a_complete_report_is_kept_in_the_asset_history(): void
    {
        $asset = Asset::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->put(route('hardware.update', $asset), $this->edit($asset) + [
                'damage_description' => 'Screen cracked and does not power on',
                'damage_repairable' => 'no',
                'damage_repair_attempted' => '1',
                'damage_repair_details' => 'Vendor replaced the board in October; it failed again',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($this->damaged->id, $asset->fresh()->status_id);
        $log = Actionlog::where('item_id', $asset->id)->where('action_type', 'damage reported')->sole();
        $report = json_decode($log->log_meta, true)['_damage_report'];
        $this->assertSame('Screen cracked and does not power on', $report['description']);
        $this->assertSame('no', $report['repairable']);
        $this->assertTrue($report['repair_attempted']);
        $this->assertSame('Vendor replaced the board in October; it failed again', $report['repair_details']);
        $this->assertSame('Damage', $report['status_to']);
    }

    public function test_a_previous_repair_attempt_must_be_described(): void
    {
        $asset = Asset::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->put(route('hardware.update', $asset), $this->edit($asset) + [
                'damage_description' => 'Keyboard does not respond',
                'damage_repairable' => 'unknown',
                'damage_repair_attempted' => '1',
            ])
            ->assertSessionHasErrors('damage_repair_details');
    }

    public function test_other_statuses_do_not_need_a_report(): void
    {
        $asset = Asset::factory()->create();
        $other = Statuslabel::factory()->create(['name' => 'Lost/Stolen']);

        $this->actingAs(User::factory()->superuser()->create())
            ->put(route('hardware.update', $asset), ['status_id' => $other->id] + $this->edit($asset))
            ->assertSessionHasNoErrors();

        $this->assertSame($other->id, $asset->fresh()->status_id);
    }

    public function test_checking_in_as_damaged_requires_a_report(): void
    {
        $asset = Asset::factory()->assignedToUser()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('hardware.checkin.store', $asset), ['status_id' => $this->damaged->id])
            ->assertSessionHasErrors('damage_description');

        $this->assertNotNull($asset->fresh()->assigned_to);
    }

    public function test_the_api_also_requires_the_report(): void
    {
        $asset = Asset::factory()->create();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson(route('api.assets.update', $asset), ['status_id' => $this->damaged->id])
            ->assertStatusMessageIs('error');

        $this->assertNotSame($this->damaged->id, $asset->fresh()->status_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function edit(Asset $asset): array
    {
        return [
            'status_id' => $this->damaged->id,
            'asset_tags' => $asset->asset_tag,
            'model_id' => $asset->model_id,
        ];
    }
}
