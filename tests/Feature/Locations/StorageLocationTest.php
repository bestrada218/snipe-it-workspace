<?php

namespace Tests\Feature\Locations;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Tests\TestCase;

class StorageLocationTest extends TestCase
{
    public function test_a_location_can_be_marked_as_storage(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('locations.store'), ['name' => 'Store room', 'is_storage' => 1])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Location::where('name', 'Store room')->sole()->is_storage);
    }

    public function test_fixed_assets_exclude_storage_and_other_categories(): void
    {
        $fixedCategory = Category::factory()->assetLaptopCategory()->create(['name' => 'Fixed equipment']);
        $installedRoom = Location::factory()->create();
        $storeRoom = Location::factory()->create(['is_storage' => true]);

        $installed = Asset::factory()->assignedToLocation($installedRoom)->create();
        $installed->model->update(['category_id' => $fixedCategory->id]);
        $stored = Asset::factory()->assignedToLocation($storeRoom)->create();
        $stored->model->update(['category_id' => $fixedCategory->id]);
        $otherCategory = Asset::factory()->assignedToLocation($installedRoom)->create();

        $ids = Asset::inInventoryLocationModule(null, [$fixedCategory->id])->pluck('id')->all();

        $this->assertContains($installed->id, $ids);
        $this->assertNotContains($stored->id, $ids);
        $this->assertNotContains($otherCategory->id, $ids);
    }
}
