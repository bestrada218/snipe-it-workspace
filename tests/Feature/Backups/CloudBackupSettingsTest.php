<?php

namespace Tests\Feature\Backups;

use App\Http\Middleware\RequireSensitiveConfirmation;
use App\Models\BackupCloudSetting;
use App\Models\User;
use App\Services\Backups\CloudBackupSettings;
use Google2FA;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Events\UnhealthyBackupWasFound;
use Tests\TestCase;
use ZipArchive;

/**
 * Settings → Backups → Cloud copies: superadmins only, behind a fresh
 * password and two-factor check, credentials stored encrypted.
 */
class CloudBackupSettingsTest extends TestCase
{
    private function enrolledSuperuser(): User
    {
        return User::factory()->superuser()->create([
            'password' => bcrypt('correct-password'),
            'two_factor_secret' => Google2FA::generateSecretKey(),
            'two_factor_enrolled' => 1,
        ]);
    }

    /** A superuser who already confirmed in this session. */
    private function confirmed(User $user): static
    {
        return $this->actingAs($user)->withSession([
            RequireSensitiveConfirmation::SESSION_KEY => ['user' => $user->id, 'at' => now()->getTimestamp()],
        ]);
    }

    /** A superuser on the open page, whose forms carry its token. */
    private function onPage(User $user): static
    {
        return $this->actingAs($user)->withSession([
            RequireSensitiveConfirmation::PAGE_TOKEN => ['user' => $user->id, 'at' => now()->getTimestamp(), 'token' => 'page-token'],
        ]);
    }

    public function test_page_renders(): void
    {
        $this->confirmed($this->enrolledSuperuser())
            ->get(route('settings.cloud_backups.index'))
            ->assertOk()
            ->assertSee(trans('cloud-backups.destinations.s3'))
            ->assertSee(trans('cloud-backups.destinations.google'));
    }

    public function test_requires_permission(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('settings.cloud_backups.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('sensitive.confirm'))->assertForbidden();
    }

    public function test_a_superuser_must_confirm_password_and_code_first(): void
    {
        $user = $this->enrolledSuperuser();

        $this->actingAs($user)->get(route('settings.cloud_backups.index'))
            ->assertRedirect(route('sensitive.confirm'));

        $this->actingAs($user)->post(route('sensitive.confirm.store'), [
            'password' => 'wrong-password',
            'two_factor_code' => Google2FA::getCurrentOtp($user->two_factor_secret),
        ])->assertSessionHasErrors('password');

        $this->actingAs($user)->post(route('sensitive.confirm.store'), [
            'password' => 'correct-password',
            'two_factor_code' => '000000',
        ])->assertSessionHasErrors('two_factor_code');

        $this->actingAs($user)->get(route('settings.cloud_backups.index'))
            ->assertRedirect(route('sensitive.confirm'));

        $this->actingAs($user)->post(route('sensitive.confirm.store'), [
            'password' => 'correct-password',
            'two_factor_code' => Google2FA::getCurrentOtp($user->two_factor_secret),
        ])->assertRedirect(route('settings.cloud_backups.index'));

        $this->actingAs($user)->get(route('settings.cloud_backups.index'))->assertOk();
    }

    public function test_it_asks_again_every_time_the_page_is_opened(): void
    {
        $user = $this->enrolledSuperuser();
        $this->actingAs($user)->post(route('sensitive.confirm.store'), [
            'password' => 'correct-password',
            'two_factor_code' => Google2FA::getCurrentOtp($user->two_factor_secret),
        ]);

        $this->actingAs($user)->get(route('settings.cloud_backups.index'))->assertOk();
        // Leaving and coming back a minute later, or reloading, asks again.
        $this->actingAs($user)->get(route('settings.cloud_backups.index'))
            ->assertRedirect(route('sensitive.confirm'));
    }

    public function test_a_pass_that_was_not_used_expires(): void
    {
        $user = $this->enrolledSuperuser();
        $this->confirmed($user);
        $this->travel(RequireSensitiveConfirmation::MINUTES + 1)->minutes();

        $this->actingAs($user)->get(route('settings.cloud_backups.index'))
            ->assertRedirect(route('sensitive.confirm'));
    }

    public function test_forms_need_the_token_of_the_open_page(): void
    {
        $user = $this->enrolledSuperuser();

        $this->onPage($user)->put(route('settings.cloud_backups.save'), ['_sensitive' => 'forged'])
            ->assertRedirect(route('sensitive.confirm'));
        $this->assertSame(0, BackupCloudSetting::count());

        // Saving from the page goes back to it without asking again.
        $this->onPage($user)->put(route('settings.cloud_backups.save'), ['_sensitive' => 'page-token', 'password' => 'a-long-backup-password', 'password_confirmation' => 'a-long-backup-password'])
            ->assertRedirect(route('settings.cloud_backups.index'));
        $this->actingAs($user)->get(route('settings.cloud_backups.index'))->assertOk();
    }

    public function test_someone_without_two_factor_has_to_enroll_to_get_in(): void
    {
        $user = User::factory()->superuser()->create(['password' => bcrypt('correct-password'), 'two_factor_secret' => null, 'two_factor_enrolled' => 0]);

        $this->actingAs($user)->get(route('sensitive.confirm'))
            ->assertOk()
            ->assertSee(trans('cloud-backups.enroll_intro'));
        $pending = session('sensitive_2fa_pending_secret');
        $this->assertNotEmpty($pending);

        // The password alone is not enough.
        $this->actingAs($user)->post(route('sensitive.confirm.store'), ['password' => 'correct-password', 'two_factor_code' => ''])
            ->assertSessionHasErrors('two_factor_code');

        $this->actingAs($user)->post(route('sensitive.confirm.store'), [
            'password' => 'correct-password',
            'two_factor_code' => Google2FA::getCurrentOtp($pending),
        ])->assertRedirect(route('settings.cloud_backups.index'));

        $user->refresh();
        $this->assertSame(1, (int) $user->two_factor_enrolled);
        $this->assertSame($pending, $user->two_factor_secret);
    }

    public function test_credentials_are_stored_encrypted_and_kept_when_left_blank(): void
    {
        $user = $this->enrolledSuperuser();

        $this->onPage($user)->put(route('settings.cloud_backups.save'), [
            '_sensitive' => 'page-token',
            'password' => 'a-long-backup-password',
            'password_confirmation' => 'a-long-backup-password',
            's3' => ['enabled' => 1, 'key' => 'AKIAEXAMPLE', 'secret' => 'super-secret-value', 'region' => 'us-east-1', 'bucket' => 'workspace-backups'],
        ])->assertRedirect(route('settings.cloud_backups.index'))->assertSessionHasNoErrors();

        $raw = (string) DB::table('backup_cloud_settings')->value('settings');
        $this->assertStringNotContainsString('super-secret-value', $raw);
        $this->assertStringNotContainsString('a-long-backup-password', $raw);

        // Saving again without retyping the secrets keeps them.
        $this->onPage($user)->put(route('settings.cloud_backups.save'), [
            '_sensitive' => 'page-token',
            's3' => ['enabled' => 1, 'key' => 'AKIAEXAMPLE', 'region' => 'eu-west-1', 'bucket' => 'workspace-backups'],
        ])->assertSessionHasNoErrors();

        $saved = CloudBackupSettings::saved();
        $this->assertSame('super-secret-value', $saved['s3']['secret']);
        $this->assertSame('a-long-backup-password', $saved['password']);
        $this->assertSame('eu-west-1', $saved['s3']['region']);

        // Secrets are never sent back to the browser.
        $this->confirmed($user)->get(route('settings.cloud_backups.index'))
            ->assertDontSee('super-secret-value')
            ->assertDontSee('a-long-backup-password');
    }

    public function test_an_enabled_destination_needs_its_data_and_a_password(): void
    {
        $this->onPage($this->enrolledSuperuser())->put(route('settings.cloud_backups.save'), [
            '_sensitive' => 'page-token',
            's3' => ['enabled' => 1],
            'google' => ['enabled' => 1, 'service_account_json' => '{"type":"user"}'],
        ])->assertSessionHasErrors(['password', 's3.key', 's3.secret', 's3.bucket', 'google.folder_id', 'google.service_account_json']);

        $this->assertSame(0, BackupCloudSetting::count());
    }

    public function test_saved_destinations_are_added_to_the_backup_config(): void
    {
        CloudBackupSettings::save([
            'password' => 'a-long-backup-password',
            'google' => ['enabled' => 1, 'service_account_json' => '{"type":"service_account"}', 'folder_id' => 'folder-123', 'shared_drive' => 1],
        ], $this->enrolledSuperuser());
        config(['backup.backup.destination.disks' => ['backup'], 'backup.backup.password' => null]);

        CloudBackupSettings::apply();

        $this->assertSame(['backup', 'backup_google'], config('backup.backup.destination.disks'));
        $this->assertSame('a-long-backup-password', config('backup.backup.password'));
        $this->assertSame('folder-123', config('filesystems.disks.backup_google.folder_id'));
        $this->assertTrue(config('filesystems.disks.backup_google.shared_drive'));
    }

    public function test_a_password_saved_in_the_ui_encrypts_the_backup(): void
    {
        $workDir = storage_path('framework/testing/cloud-settings-'.uniqid());
        File::ensureDirectoryExists($workDir.'/source');
        File::put($workDir.'/source/evidence.pdf', 'approval evidence');
        Storage::fake('backup');
        Storage::fake('backup_s3');

        CloudBackupSettings::save(['password' => 'a-long-backup-password'], $this->enrolledSuperuser());
        config([
            'backup.backup.source.files.include' => [$workDir.'/source'],
            'backup.backup.source.files.relative_path' => $workDir,
            'backup.backup.source.databases' => [],
            'backup.backup.destination.disks' => ['backup', 'backup_s3'],
            'backup.backup.password' => null,
        ]);

        $this->artisan('snipeit:backup', ['--filename' => 'ui-password'])
            ->expectsOutputToContain('Backup saved on: backup, backup_s3.')
            ->assertSuccessful();

        $zip = new ZipArchive;
        $copies = Storage::disk('backup_s3')->files('backups');
        $this->assertCount(1, $copies);
        $this->assertTrue($zip->open(Storage::disk('backup_s3')->path($copies[0])));
        $name = collect(range(0, $zip->numFiles - 1))->map(fn (int $i) => $zip->getNameIndex($i))->first(fn (string $n) => str_ends_with($n, 'evidence.pdf'));
        $this->assertFalse(@$zip->getFromName($name));
        $zip->setPassword('a-long-backup-password');
        $this->assertSame('approval evidence', $zip->getFromName($name));
        $zip->close();

        $this->assertTrue(CloudBackupSettings::results()['backup_s3']['ok']);
        File::deleteDirectory($workDir);
    }

    public function test_the_daily_monitor_also_watches_cloud_destinations_set_up_in_the_ui(): void
    {
        Event::fake([UnhealthyBackupWasFound::class]);
        Storage::fake('backup');
        Storage::disk('backup')->put('backups/2026-11-25-01-30-00.zip', 'zip');

        CloudBackupSettings::save([
            'password' => 'a-long-backup-password',
            // An S3 destination nobody can reach: it must be reported.
            's3' => ['enabled' => 1, 'key' => 'AKIAEXAMPLE', 'secret' => 'secret', 'bucket' => 'workspace-backups', 'endpoint' => 'http://127.0.0.1:9'],
        ], $this->enrolledSuperuser());
        config(['backup.backup.destination.disks' => ['backup'], 'backup.monitor_backups.0.disks' => ['backup']]);

        $this->artisan('snipeit:backup-monitor')
            ->expectsOutputToContain('backups on the backup disk are considered healthy')
            ->expectsOutputToContain('backups on the backup_s3 disk are considered unhealthy')
            ->assertFailed();

        Event::assertDispatched(UnhealthyBackupWasFound::class, fn ($event) => $event->backupDestinationStatus->backupDestination()->diskName() === 'backup_s3');
    }
}
