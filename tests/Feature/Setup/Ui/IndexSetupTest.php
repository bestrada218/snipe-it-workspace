<?php

namespace Tests\Feature\Setup\Ui;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\FirstAdminNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class IndexSetupTest extends TestCase
{
    public function test_page_renders()
    {
        $this->get(route('setup'))
            ->assertOk();
    }

    public function test_page_redirects_if_no_records_found()
    {
        $this->assertDatabaseEmpty('users');

        $this->get(route('home'))
            ->assertStatus(302)
            ->assertRedirectToRoute('setup');
    }

    public function test_page_redirects_if_records_found()
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('setup'))
            ->assertStatus(302)
            ->assertRedirectToRoute('home');
    }

    public function test_migration_page_renders()
    {

        $this->assertDatabaseEmpty('users');

        $this->post(route('setup.migrate'))
            ->assertOk();

    }

    public function test_create_first_user_page_renders()
    {

        $this->assertDatabaseEmpty('users');

        $this->get(route('setup.user'))
            ->assertOk();

    }

    public function test_create_first_user_validation()
    {

        $this->assertDatabaseEmpty('users');

        $response = $this->post(route('setup.user.save'))
            ->assertStatus(302);

        $this->followRedirects($response)->assertSee('error');
        $this->assertDatabaseCount('users', 0);

    }

    public function test_create_first_user_saved()
    {

        $this->assertDatabaseEmpty('users');
        Notification::fake();

        $this->post(route('setup.user.save'),
            [
                'site_name' => 'Snipe-IT',
                'first_name' => 'First',
                'last_name' => 'Admin',
                'username' => 'AwesomeAdmin',
                'email' => 'admin@example.org',
                'email_creds' => 1,
                'password' => '0834529!!*423',
                'password_confirmation' => '0834529!!*423',
                'email_domain' => 'example.org',
            ])
            ->assertRedirectToRoute('setup.done')
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('3', (string) Setting::latest('id')->firstOrFail()->two_factor_enabled);
        Notification::assertSentTo(User::firstOrFail(), FirstAdminNotification::class, function (FirstAdminNotification $notification): bool {
            $this->assertArrayNotHasKey('password', $notification->_data);
            $this->assertArrayNotHasKey('username', $notification->_data);

            return true;
        });

    }

    public function test_setup_mail_failure_does_not_expose_transport_details(): void
    {
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Internal SMTP diagnostic'));

        $this->postJson(route('setup.mailtest'))
            ->assertStatus(502)
            ->assertDontSee('Internal SMTP diagnostic')
            ->assertSee(trans('mail-settings.transport_problem'));
    }
}
