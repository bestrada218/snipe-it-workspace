<?php

namespace Tests\Feature\AssetTags;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\User;
use App\Services\AssetTagSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssetTagSequenceTest extends TestCase
{
    public function test_a_sequence_used_by_an_asset_cannot_be_reused_by_an_accessory(): void
    {
        Asset::factory()->create(['asset_tag' => 'NY-017-LP-W11']);

        try {
            Accessory::factory()->create(['asset_tag' => 'NJ-017-HS']);
            $this->fail('The repeated sequence was accepted.');
        } catch (ValidationException $error) {
            $this->assertStringContainsString('NY-017-LP-W11', $error->errors()['asset_tag'][0]);
        }

        $this->assertFalse(Accessory::where('asset_tag', 'NJ-017-HS')->exists());
    }

    public function test_the_create_form_rejects_a_repeated_sequence(): void
    {
        Asset::factory()->create(['asset_tag' => 'NY-018-LP-W11']);
        $template = Asset::factory()->make();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('hardware.store'), [
                'asset_tags' => [1 => 'NJ-018-DT-LNX'],
                'model_id' => $template->model_id,
                'status_id' => $template->status_id,
            ])
            ->assertSessionHasErrors('asset_tag');

        $this->assertFalse(Asset::where('asset_tag', 'NJ-018-DT-LNX')->exists());
    }

    public function test_a_record_can_be_saved_again_with_its_own_sequence(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'NY-019-LP-W11']);

        $asset->name = 'Reception laptop';
        $asset->save();
        $asset->asset_tag = 'NY-019-LP-MAC';
        $asset->save();

        $this->assertSame('NY-019-LP-MAC', $asset->fresh()->asset_tag);
    }

    public function test_changing_the_tag_frees_the_old_sequence(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'NY-020-LP-W11']);
        $asset->update(['asset_tag' => 'NY-021-LP-W11']);

        Accessory::factory()->create(['asset_tag' => 'NY-020-HS']);

        $this->assertTrue(Accessory::where('asset_tag', 'NY-020-HS')->exists());
    }

    public function test_a_deleted_record_keeps_its_sequence(): void
    {
        Asset::factory()->create(['asset_tag' => 'NY-022-LP-W11'])->delete();

        $this->expectException(ValidationException::class);
        Accessory::factory()->create(['asset_tag' => 'NY-022-KB']);
    }

    public function test_tags_outside_the_format_are_not_restricted(): void
    {
        Asset::factory()->create(['asset_tag' => 'LEGACY-0001']);
        Asset::factory()->create(['asset_tag' => 'LEGACY-0002']);

        $this->assertSame(2, Asset::where('asset_tag', 'like', 'LEGACY-%')->count());
    }

    public function test_the_next_sequence_follows_the_highest_one_in_use(): void
    {
        Asset::factory()->create(['asset_tag' => 'NY-041-LP-W11']);
        Accessory::factory()->create(['asset_tag' => 'NJ-045-HS']);

        $this->assertSame('046', AssetTagSequence::next());
    }

    public function test_the_report_lists_repeated_sequences(): void
    {
        Asset::factory()->create(['asset_tag' => 'NY-030-LP-W11']);
        $accessory = Accessory::factory()->create(['asset_tag' => 'NY-031-HS']);
        DB::table('accessories')->where('id', $accessory->id)->update(['asset_tag' => 'NJ-030-HS']);

        $this->artisan('snipeit:asset-tag-duplicates')
            ->expectsOutputToContain('NJ-030-HS')
            ->assertFailed();
    }

    public function test_a_record_is_not_created_when_its_number_is_claimed_meanwhile(): void
    {
        $holder = Asset::factory()->create(['asset_tag' => 'LEGACY-HOLDER']);
        // Runs after the availability check: another save claims 500 first.
        Accessory::saving(function (Accessory $accessory) use ($holder) {
            if ($accessory->asset_tag === 'NJ-500-HS' && ! DB::table('asset_tag_sequences')->where('sequence', 500)->exists()) {
                DB::table('asset_tag_sequences')->insert(['sequence' => 500, 'item_type' => Asset::class, 'item_id' => $holder->id, 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        try {
            Accessory::factory()->create(['asset_tag' => 'NJ-500-HS']);
            $this->fail('The claimed number was accepted.');
        } catch (ValidationException) {
        }

        $this->assertFalse(Accessory::where('asset_tag', 'NJ-500-HS')->exists());
    }

    public function test_a_tag_from_the_builder_takes_the_next_free_number(): void
    {
        Asset::factory()->create(['asset_tag' => 'NY-501-LP-W11']);
        $template = Asset::factory()->make();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('hardware.store'), [
                'asset_tags' => [1 => 'NJ-501-DT-W11'],
                'asset_tag_from_builder' => 1,
                'model_id' => $template->model_id,
                'status_id' => $template->status_id,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('warning');

        $this->assertTrue(Asset::where('asset_tag', 'NJ-502-DT-W11')->exists());
        $this->assertFalse(Asset::where('asset_tag', 'NJ-501-DT-W11')->exists());
    }

    public function test_a_builder_tag_retries_when_the_number_is_claimed_meanwhile(): void
    {
        $holder = Asset::factory()->create(['asset_tag' => 'LEGACY-HOLDER']);
        Accessory::saving(function (Accessory $accessory) use ($holder) {
            if ($accessory->asset_tag === 'NJ-600-HS' && ! DB::table('asset_tag_sequences')->where('sequence', 600)->exists()) {
                DB::table('asset_tag_sequences')->insert(['sequence' => 600, 'item_type' => Asset::class, 'item_id' => $holder->id, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
        request()->merge(['asset_tag_from_builder' => 1]);

        $accessory = Accessory::factory()->create(['asset_tag' => 'NJ-600-HS']);

        $this->assertSame('NJ-601-HS', $accessory->fresh()->asset_tag);
        $this->assertSame(1, Accessory::where('asset_tag', 'like', 'NJ-60%')->count());
    }
}
