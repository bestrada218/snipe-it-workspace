<?php

namespace Tests\Feature\LocationScope;

use App\Models\Asset;
use App\Models\Group;
use App\Models\License;
use App\Models\Location;
use App\Models\User;
use Database\Factories\UserFactory;
use Tests\TestCase;

class LocationGroupScopeTest extends TestCase
{
    private Location $newYork;

    private Location $newYorkFloor;

    private Location $newJersey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->newYork = Location::factory()->create(['name' => 'Nueva York']);
        $this->newYorkFloor = Location::factory()->create(['name' => 'Nueva York piso 3', 'parent_id' => $this->newYork->id]);
        $this->newJersey = Location::factory()->create(['name' => 'Nueva Jersey']);
    }

    public function test_scoped_user_only_lists_assets_inside_their_location_tree(): void
    {
        $inRoot = $this->assetAt($this->newYork);
        $inChild = $this->assetAt($this->newYorkFloor);
        $outside = $this->assetAt($this->newJersey);

        $ids = collect($this->actingAsForApi($this->scopedUser(User::factory()->viewAssets(), $this->newYork))
            ->getJson(route('api.assets.index', ['limit' => 50]))
            ->assertOk()
            ->json('rows'))
            ->pluck('id');

        $this->assertTrue($ids->contains($inRoot->id));
        $this->assertTrue($ids->contains($inChild->id));
        $this->assertFalse($ids->contains($outside->id));
    }

    public function test_scoped_user_cannot_open_an_asset_outside_their_tree(): void
    {
        $user = $this->scopedUser(User::factory()->viewAssets(), $this->newYork);
        $inside = $this->assetAt($this->newYorkFloor);
        $outside = $this->assetAt($this->newJersey);

        $this->actingAs($user)
            ->get(route('hardware.show', $inside))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('hardware.show', $outside))
            ->assertRedirect(route('hardware.index'))
            ->assertSessionHas('error');
    }

    public function test_scoped_user_cannot_move_an_asset_out_of_their_tree(): void
    {
        $asset = $this->assetAt($this->newYork);

        $this->actingAsForApi($this->scopedUser(User::factory()->viewAssets()->editAssets(), $this->newYork))
            ->patchJson(route('api.assets.update', $asset), ['location_id' => $this->newJersey->id])
            ->assertOk()
            ->assertStatusMessageIs('error');

        $this->assertSame($this->newYork->id, (int) $asset->fresh()->location_id);
    }

    public function test_scoped_user_cannot_see_licenses(): void
    {
        License::factory()->create();

        $this->actingAs($this->scopedUser(User::factory()->viewLicenses(), $this->newYork))
            ->get(route('licenses.index'))
            ->assertForbidden();
    }

    public function test_two_scoped_groups_only_allow_their_common_locations(): void
    {
        $user = $this->scopedUser(User::factory()->viewAssets(), $this->newYork);
        $user->groups()->attach(Group::factory()->create(['scope_location_id' => $this->newYorkFloor->id]));
        $inRoot = $this->assetAt($this->newYork);
        $inChild = $this->assetAt($this->newYorkFloor);

        $ids = collect($this->actingAsForApi($user)
            ->getJson(route('api.assets.index', ['limit' => 50]))
            ->assertOk()
            ->json('rows'))
            ->pluck('id');

        $this->assertFalse($ids->contains($inRoot->id));
        $this->assertTrue($ids->contains($inChild->id));
    }

    public function test_scoped_user_without_a_location_can_still_use_the_app(): void
    {
        $user = $this->scopedUser(User::factory()->viewAssets(), $this->newYork, livesAtRoot: false);
        $inRoot = $this->assetAt($this->newYork);

        $this->actingAs($user)
            ->get(route('hardware.index'))
            ->assertOk();

        $this->actingAsForApi($user)
            ->getJson(route('api.assets.index', ['limit' => 50]))
            ->assertOk()
            ->assertJsonPath('rows.0.id', $inRoot->id);
    }

    public function test_scoped_user_cannot_see_people_outside_their_tree(): void
    {
        $inside = User::factory()->create(['location_id' => $this->newYorkFloor->id]);
        $outside = User::factory()->create(['location_id' => $this->newJersey->id]);

        $ids = collect($this->actingAsForApi($this->scopedUser(User::factory()->viewUsers(), $this->newYork))
            ->getJson(route('api.users.index', ['limit' => 50]))
            ->assertOk()
            ->json('rows'))
            ->pluck('id');

        $this->assertTrue($ids->contains($inside->id));
        $this->assertFalse($ids->contains($outside->id));
    }

    public function test_superuser_is_never_scoped(): void
    {
        $outside = $this->assetAt($this->newJersey);
        $superuser = $this->scopedUser(User::factory()->superuser(), $this->newYork);

        $ids = collect($this->actingAsForApi($superuser)
            ->getJson(route('api.assets.index', ['limit' => 50]))
            ->assertOk()
            ->json('rows'))
            ->pluck('id');

        $this->assertTrue($ids->contains($outside->id));
    }

    private function scopedUser(UserFactory $factory, Location $root, bool $livesAtRoot = true): User
    {
        $user = $factory->create(['location_id' => $livesAtRoot ? $root->id : null]);
        $user->groups()->attach(Group::factory()->create(['scope_location_id' => $root->id]));

        return $user;
    }

    private function assetAt(Location $location): Asset
    {
        return Asset::factory()->create(['location_id' => $location->id, 'rtd_location_id' => $location->id]);
    }
}
