<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\RequireSensitiveConfirmation;
use App\Http\Middleware\SecurityHeaders;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FilenameTraversalMitigationTest extends TestCase
{
    public function test_settings_backup_download_rejects_nested_filename_input(): void
    {
        config(['app.lock_passwords' => false]);

        $user = User::factory()->superuser()->create();
        $this->actingAs($user)->withSession([
            RequireSensitiveConfirmation::SESSION_KEY => ['user' => $user->id, 'at' => now()->getTimestamp(), 'scope' => 'settings.backups.download'],
        ])
            ->get('/admin/backups/download/..')
            ->assertRedirect(route('settings.backups.index'))
            ->assertSessionHas('error', trans('admin/settings/message.backup.file_not_found'));
    }

    public function test_a_web_backup_download_requires_fresh_confirmation(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('settings.backups.download', 'example.zip'))
            ->assertRedirect(route('sensitive.confirm'))
            ->assertSessionHas(RequireSensitiveConfirmation::INTENDED, [
                'scope' => 'settings.backups.download', 'parameters' => ['filename' => 'example.zip'],
            ]);
    }

    public function test_even_a_superadmin_cannot_download_backups_through_the_api_by_default(): void
    {
        $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.settings.backups.latest'))->assertForbidden();
    }

    public function test_opted_in_backup_api_returns_not_found_when_no_archive_exists(): void
    {
        config(['app.allow_backup_api' => true]);
        Storage::fake(config('filesystems.default'));

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.settings.backups.latest'))->assertNotFound();
    }

    public function test_secure_pages_restrict_browser_features_and_framing(): void
    {
        config(['app.debug' => false, 'app.enable_csp' => true, 'app.allow_iframing' => false, 'app.enable_hsts' => true]);
        User::factory()->create();
        $this->withMiddleware(SecurityHeaders::class);

        $response = $this->get(str_replace('http://', 'https://', route('login')));
        $response->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Strict-Transport-Security')
            ->assertHeader('Permissions-Policy');
        $this->assertStringContainsString("base-uri 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_settings_backup_delete_rejects_nested_filename_input(): void
    {
        config(['app.lock_passwords' => false]);
        config(['app.allow_backup_delete' => 'true']);

        $this->actingAs(User::factory()->superuser()->create())
            ->delete('/admin/backups/delete/..')
            ->assertRedirect(route('settings.backups.index'))
            ->assertSessionHas('error', trans('admin/settings/message.backup.file_not_found'));
    }

    public function test_settings_backup_restore_rejects_nested_filename_input(): void
    {
        config(['app.lock_passwords' => false]);

        $this->actingAs(User::factory()->superuser()->create())
            ->post('/admin/backups/restore/..')
            ->assertRedirect(route('settings.backups.index'))
            ->assertSessionHas('error', trans('admin/settings/message.backup.file_not_found'));
    }

    public function test_storage_proxy_blocks_path_traversal_segments(): void
    {
        $this->withoutMiddleware();
        config(['filesystems.public_proxy' => true]);

        Storage::disk('public')->put('proxy-safe/example.txt', 'ok');

        $this->get('/storage-proxy/..%2Fproxy-safe%2Fexample.txt')
            ->assertNotFound();
    }

    public function test_storage_proxy_serves_valid_public_path(): void
    {
        $this->withoutMiddleware();
        config(['filesystems.public_proxy' => true]);

        Storage::disk('public')->put('proxy-safe/example-valid.txt', 'ok');

        $response = $this->get('/storage-proxy/proxy-safe/example-valid.txt')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringContainsString('ok', $response->streamedContent());
    }

    public function test_storage_proxy_does_not_serve_files_without_explicit_configuration(): void
    {
        $this->withoutMiddleware();
        config(['filesystems.public_proxy' => false]);
        Storage::disk('public')->put('proxy-safe/disabled.txt', 'example');

        $this->get('/storage-proxy/proxy-safe/disabled.txt')->assertNotFound();
    }

    public function test_a_public_s3_proxy_with_an_overlapping_private_prefix_is_denied(): void
    {
        $this->withoutMiddleware();
        config([
            'filesystems.public_proxy' => true,
            'filesystems.disks.public' => ['driver' => 's3', 'bucket' => 'example', 'root' => 'files'],
            'filesystems.disks.backup' => ['driver' => 's3', 'bucket' => 'example', 'root' => 'files/backups'],
        ]);

        $this->get('/storage-proxy/example.png')->assertNotFound();
    }
}
