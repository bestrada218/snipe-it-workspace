<?php

namespace Tests\Feature\PredefinedKits\Ui;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\PredefinedKit;
use App\Models\User;
use Tests\TestCase;

class CheckoutKitKeepsAssetNameTest extends TestCase
{
    public function test_kit_checkout_keeps_the_asset_name(): void
    {
        $model = AssetModel::factory()->create();
        $asset = Asset::factory()->create(['model_id' => $model->id, 'name' => 'Reception laptop', 'assigned_to' => null]);
        $kit = PredefinedKit::factory()->create();
        $kit->models()->attach($model->id, ['quantity' => 1]);
        $person = User::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('kits.checkout.store', $kit), ['user_id' => $person->id]);

        $asset->refresh();
        $this->assertSame($person->id, (int) $asset->assigned_to);
        $this->assertSame('Reception laptop', $asset->name);
    }
}
