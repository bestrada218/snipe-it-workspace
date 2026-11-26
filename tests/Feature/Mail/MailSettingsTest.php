<?php

namespace Tests\Feature\Mail;

use App\Http\Middleware\RequireSensitiveConfirmation;
use App\Models\User;
use App\Services\Mail\MailSettings;
use Google2FA;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MailSettingsTest extends TestCase
{
    private function superuser(): User
    {
        return User::factory()->superuser()->create([
            'two_factor_secret' => Google2FA::generateSecretKey(),
            'two_factor_enrolled' => 1,
        ]);
    }

    private function onPage(User $user): static
    {
        return $this->actingAs($user)->withSession([
            RequireSensitiveConfirmation::PAGE_TOKEN => ['user' => $user->id, 'at' => now()->getTimestamp(), 'token' => 'page-token', 'scope' => 'settings.mail.index'],
        ]);
    }

    public function test_page_renders(): void
    {
        $user = $this->superuser();

        $this->actingAs($user)->withSession([RequireSensitiveConfirmation::SESSION_KEY => ['user' => $user->id, 'at' => now()->getTimestamp(), 'scope' => 'settings.mail.index']])
            ->get(route('settings.mail.index'))
            ->assertOk()
            ->assertSee(trans('mail-settings.providers.google'))
            ->assertSee(trans('mail-settings.guides.sendgrid.2'))
            ->assertSee(trans('mail-settings.digest_title'));
    }

    public function test_requires_permission(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('settings.mail.index'))->assertForbidden();

        // A superuser still has to confirm password and two-factor code.
        $this->actingAs($this->superuser())->get(route('settings.mail.index'))->assertRedirect(route('sensitive.confirm'));
    }

    public function test_the_server_is_saved_encrypted_and_used_for_every_mail(): void
    {
        $user = $this->superuser();

        $this->onPage($user)->put(route('settings.mail.save'), [
            '_sensitive' => 'page-token',
            'enabled' => 1,
            'provider' => 'sendgrid',
            'host' => 'smtp.sendgrid.net',
            'port' => 465,
            'username' => 'apikey',
            'password' => 'SG.secret-key',
            'from_address' => 'inventory@example.com',
            'from_name' => 'IT Inventory',
        ])->assertRedirect(route('settings.mail.index'))->assertSessionHasNoErrors();

        $this->assertStringNotContainsString('SG.secret-key', (string) DB::table('mail_settings')->value('settings'));

        $audit = (string) DB::table('action_logs')->where('created_by', $user->id)->where('note', 'like', 'SMTP settings%')->value('note');
        $this->assertStringContainsString('password', $audit);
        $this->assertStringNotContainsString('SG.secret-key', $audit);

        MailSettings::apply();
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.sendgrid.net', config('mail.mailers.smtp.host'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertTrue(config('mail.mailers.smtp.require_tls'));
        $this->assertTrue(config('mail.mailers.smtp.verify_peer'));
        $this->assertSame('SG.secret-key', config('mail.mailers.smtp.password'));
        $this->assertSame('inventory@example.com', config('mail.from.address'));

        // Saving again without the password keeps it; it is never shown.
        $this->onPage($user)->put(route('settings.mail.save'), [
            '_sensitive' => 'page-token', 'enabled' => 1, 'provider' => 'sendgrid', 'host' => 'smtp.sendgrid.net', 'port' => 587, 'from_address' => 'inventory@example.com',
        ])->assertSessionHasNoErrors();
        $this->assertSame('SG.secret-key', MailSettings::saved()['password']);
        $this->actingAs($user)->withSession([RequireSensitiveConfirmation::SESSION_KEY => ['user' => $user->id, 'at' => now()->getTimestamp(), 'scope' => 'settings.mail.index']])
            ->get(route('settings.mail.index'))->assertDontSee('SG.secret-key');
    }

    public function test_turned_on_it_needs_a_server_and_a_sender(): void
    {
        $this->onPage($this->superuser())->put(route('settings.mail.save'), ['_sensitive' => 'page-token', 'enabled' => 1, 'provider' => 'other'])
            ->assertSessionHasErrors(['host', 'port', 'from_address']);
    }

    public function test_a_test_email_reports_how_it_went(): void
    {
        $this->onPage($this->superuser())->post(route('settings.mail.test'), ['_sensitive' => 'page-token', 'test_to' => 'me@example.com'])
            ->assertSessionHas('success');

        $this->assertTrue(MailSettings::status()['test']['ok']);
        $this->assertSame('me@example.com', MailSettings::status()['test']['to']);
    }
}
