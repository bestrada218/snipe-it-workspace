<?php

namespace Tests\Feature\AssetTags;

use App\Models\Accessory;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\User;
use App\Services\AssetTagTypes;
use Tests\TestCase;

class AssetTagTypesTest extends TestCase
{
    /**
     * @return array<int, array{original: string, code: string, name: string, suffix: int}>
     */
    private function currentRows(): array
    {
        return array_map(fn (array $type) => [
            'original' => $type['code'],
            'code' => $type['code'],
            'name' => $type['name'],
            'suffix' => (int) $type['suffix'],
        ], AssetTagTypes::all());
    }

    private function save(array $rows)
    {
        return $this->actingAs(User::factory()->superuser()->create())
            ->post(route('settings.asset_tag_types.save'), ['types' => $rows]);
    }

    public function test_only_superusers_manage_the_types(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings.asset_tag_types.index'))
            ->assertForbidden();

        Asset::factory()->create(['asset_tag' => 'NY-801-LP-W11']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('settings.asset_tag_types.index'))
            ->assertOk()
            ->assertSee('Laptop')
            ->assertSee(trans_choice('asset-tag-types.in_use_tags', 1, ['count' => 1]));
    }

    public function test_a_new_type_is_offered_by_the_tag_builder(): void
    {
        $this->save([...$this->currentRows(), ['original' => '', 'code' => 'tb', 'name' => 'Tablet', 'suffix' => 0]])
            ->assertSessionHasNoErrors();

        $this->assertContains('TB', AssetTagTypes::codes());
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.create'))
            ->assertSee('TB · Tablet');
    }

    public function test_changing_a_code_updates_every_tag_that_uses_it(): void
    {
        $laptop = Asset::factory()->create(['asset_tag' => 'NY-802-LP-W11']);
        $deleted = Asset::factory()->create(['asset_tag' => 'NJ-803-LP']);
        $deleted->delete();
        $accessory = Accessory::factory()->create(['asset_tag' => 'NY-804-LP']);
        $desktop = Asset::factory()->create(['asset_tag' => 'NY-805-DT-MAC']);

        $rows = array_map(fn (array $row) => $row['code'] === 'LP' ? ['code' => 'LT', 'name' => 'Laptop computer'] + $row : $row, $this->currentRows());
        $this->save($rows)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', trans_choice('asset-tag-types.saved', 3, ['count' => 3]));

        $this->assertSame('NY-802-LT-W11', $laptop->fresh()->asset_tag);
        $this->assertSame('NJ-803-LT', Asset::withTrashed()->find($deleted->id)->asset_tag);
        $this->assertSame('NY-804-LT', $accessory->fresh()->asset_tag);
        $this->assertSame('NY-805-DT-MAC', $desktop->fresh()->asset_tag);
        $this->assertNotContains('LP', AssetTagTypes::codes());
        $this->assertContains('LT', AssetTagTypes::suffixCodes());

        $log = Actionlog::where('item_type', Asset::class)->where('item_id', $laptop->id)->where('action_type', 'update')->latest('id')->first();
        $this->assertSame(['old' => 'NY-802-LP-W11', 'new' => 'NY-802-LT-W11'], json_decode($log->log_meta, true)['asset_tag']);

        // The KKK numbers stay reserved: 802 is still taken.
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Accessory::factory()->create(['asset_tag' => 'NJ-802-HS']);
    }

    public function test_two_codes_can_be_swapped_in_one_save(): void
    {
        $laptop = Asset::factory()->create(['asset_tag' => 'NY-806-LP-W11']);
        $desktop = Asset::factory()->create(['asset_tag' => 'NY-807-DT-W11']);

        $rows = array_map(fn (array $row) => match ($row['code']) {
            'LP' => ['code' => 'DT'] + $row,
            'DT' => ['code' => 'LP'] + $row,
            default => $row,
        }, $this->currentRows());
        $this->save($rows)->assertSessionHasNoErrors();

        $this->assertSame('NY-806-DT-W11', $laptop->fresh()->asset_tag);
        $this->assertSame('NY-807-LP-W11', $desktop->fresh()->asset_tag);
    }

    public function test_a_type_in_use_cannot_be_removed_but_an_unused_one_can(): void
    {
        Asset::factory()->create(['asset_tag' => 'NY-808-HS']);

        $removeHeadset = array_map(fn (array $row) => $row + ['remove' => (int) ($row['code'] === 'HS')], $this->currentRows());
        $this->save($removeHeadset)->assertSessionHasErrors();
        $this->assertContains('HS', AssetTagTypes::codes());

        $removeProjector = array_map(fn (array $row) => $row + ['remove' => (int) ($row['code'] === 'PJ')], $this->currentRows());
        $this->save($removeProjector)->assertSessionHasNoErrors();
        $this->assertNotContains('PJ', AssetTagTypes::codes());
    }

    public function test_codes_must_be_two_characters_and_unique(): void
    {
        $this->save([...$this->currentRows(), ['original' => '', 'code' => 'L', 'name' => 'Lamp', 'suffix' => 0]])
            ->assertSessionHasErrors('types.12.code');

        $this->save([...$this->currentRows(), ['original' => '', 'code' => 'LP', 'name' => 'Laptop again', 'suffix' => 0]])
            ->assertSessionHasErrors('types');

        $this->assertCount(12, AssetTagTypes::all());
    }

    public function test_the_builder_rejects_an_unknown_type(): void
    {
        $template = Asset::factory()->make();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('hardware.store'), [
                'asset_tags' => [1 => 'NJ-809-ZZ'],
                'asset_tag_from_builder' => 1,
                'model_id' => $template->model_id,
                'status_id' => $template->status_id,
            ])
            ->assertSessionHasErrors('asset_tag');

        $this->assertFalse(Asset::where('asset_tag', 'NJ-809-ZZ')->exists());
    }

    public function test_empty_added_rows_are_ignored(): void
    {
        $this->save([
            ...$this->currentRows(),
            ['original' => '', 'code' => 'pd', 'name' => 'iPad', 'suffix' => 0, 'remove' => 0],
            ['original' => '', 'code' => '', 'name' => '', 'suffix' => 0, 'remove' => 0],
            ['original' => '', 'code' => '', 'name' => '', 'suffix' => 0, 'remove' => 1],
        ])->assertSessionHasNoErrors();

        $this->assertContains('PD', AssetTagTypes::codes());
        $this->assertCount(13, AssetTagTypes::all());
    }
}
