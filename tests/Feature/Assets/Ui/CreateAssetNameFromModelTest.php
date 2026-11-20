<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Statuslabel;
use App\Models\User;
use Tests\TestCase;

class CreateAssetNameFromModelTest extends TestCase
{
    public function test_an_asset_without_a_name_takes_the_model_name(): void
    {
        $model = AssetModel::factory()->create(['name' => 'Framework Laptop 13']);

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('hardware.store'), $this->payload($model, 'NY-071-LP-W11'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Framework Laptop 13', Asset::where('asset_tag', 'NY-071-LP-W11')->sole()->name);
    }

    public function test_a_typed_name_is_kept(): void
    {
        $model = AssetModel::factory()->create(['name' => 'Framework Laptop 13']);

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('hardware.store'), $this->payload($model, 'NY-072-LP-W11') + ['name' => 'Reception laptop'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Reception laptop', Asset::where('asset_tag', 'NY-072-LP-W11')->sole()->name);
    }

    public function test_creating_with_immediate_assignment_keeps_the_name(): void
    {
        $model = AssetModel::factory()->create(['name' => 'Framework Laptop 13']);
        $person = User::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('hardware.store'), $this->payload($model, 'NY-073-LP-W11') + [
                'checkout_to_type' => 'user',
                'assigned_user' => $person->id,
            ])
            ->assertSessionHasNoErrors();

        $asset = Asset::where('asset_tag', 'NY-073-LP-W11')->sole();
        $this->assertSame($person->id, (int) $asset->assigned_to);
        $this->assertSame('Framework Laptop 13', $asset->name);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(AssetModel $model, string $tag): array
    {
        return [
            'asset_tags' => [1 => $tag],
            'model_id' => $model->id,
            'status_id' => Statuslabel::factory()->readyToDeploy()->create()->id,
        ];
    }
}
