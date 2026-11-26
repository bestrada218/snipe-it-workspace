<?php

namespace Tests\Feature\Mail;

use App\Mail\DailyDigestMail;
use App\Models\AssetReturn;
use App\Models\User;
use App\Services\Mail\DailyDigest;
use App\Services\Mail\MailSettings;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DailyDigestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // A recent backup, so only what each test sets up is reported.
        Storage::fake('backup');
        Storage::disk('backup')->put('backups/recent.zip', 'zip');
    }

    private function turnOn(int $hour): void
    {
        MailSettings::saveDigest(['enabled' => true, 'recipients' => ['lead@example.com', 'inventory@example.com'], 'hour' => $hour], User::factory()->superuser()->create());
    }

    public function test_a_return_without_progress_is_stalled_and_shows_on_the_dashboard(): void
    {
        $this->settings->set(['return_stalled_days' => 5]);
        $stalled = AssetReturn::factory()->create();
        AssetReturn::factory()->create();
        AssetReturn::whereKey($stalled->id)->update(['updated_at' => now()->subDays(6)]);

        $this->assertSame([$stalled->id], AssetReturn::stalled()->pluck('id')->all());

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('home'))
            ->assertSee(trans_choice('dashboard-attention.returns_stalled', 1))
            ->assertSee(route('returns.index', ['view' => 'stalled']), false);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('returns.index', ['view' => 'stalled']))
            ->assertOk()
            ->assertSee('#'.$stalled->id);
    }

    public function test_a_note_counts_as_progress(): void
    {
        $return = AssetReturn::factory()->create();
        AssetReturn::whereKey($return->id)->update(['updated_at' => now()->subDays(30)]);
        $return->notes()->create(['note' => 'Called the person', 'created_by' => User::factory()->create()->id]);

        $this->assertSame(0, AssetReturn::stalled()->count());
    }

    public function test_the_summary_goes_out_once_at_the_chosen_hour_when_something_is_pending(): void
    {
        Mail::fake();
        $this->travelTo(today()->setTime(7, 5));
        $this->turnOn(7);
        $return = AssetReturn::factory()->create();
        AssetReturn::whereKey($return->id)->update(['updated_at' => now()->subDays(10)]);

        $this->artisan('snipeit:daily-digest')->assertSuccessful();
        $this->artisan('snipeit:daily-digest')->assertSuccessful();

        Mail::assertSent(DailyDigestMail::class, 1);
        Mail::assertSent(DailyDigestMail::class, fn (DailyDigestMail $mail) => $mail->hasBcc('lead@example.com') && $mail->to === [] && isset($mail->sections['returns']));
    }

    public function test_nothing_is_sent_at_another_hour_or_with_nothing_pending(): void
    {
        Mail::fake();
        $this->travelTo(today()->setTime(9, 5));
        $this->turnOn(7);
        $return = AssetReturn::factory()->create();
        AssetReturn::whereKey($return->id)->update(['updated_at' => now()->subDays(10)]);

        $this->artisan('snipeit:daily-digest')->assertSuccessful();
        Mail::assertNothingSent();

        $this->turnOn(9);
        AssetReturn::query()->delete();
        $this->artisan('snipeit:daily-digest')->assertSuccessful();
        Mail::assertNothingSent();

        // "Send now" sends anyway, to see how it arrives.
        $this->artisan('snipeit:daily-digest', ['--now' => true])->assertSuccessful();
        Mail::assertSent(DailyDigestMail::class, 1);
    }

    public function test_the_summary_renders(): void
    {
        $return = AssetReturn::factory()->create();
        AssetReturn::whereKey($return->id)->update(['updated_at' => now()->subDays(10)]);

        $html = (new DailyDigestMail(DailyDigest::sections()))->render();

        $this->assertStringContainsString(trans('mail-settings.digest.sections.returns'), $html);
        $this->assertStringContainsString(route('home'), $html);
        $this->assertStringNotContainsString(route('returns.show', $return), $html);
        $this->assertStringNotContainsString(e($return->person->display_name), $html);
    }
}
