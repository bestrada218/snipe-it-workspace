<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Tests\TestCase;

class WorkspaceVersionTest extends TestCase
{
    public function test_version_comes_from_the_version_file(): void
    {
        $this->assertSame(trim(file_get_contents(base_path('VERSION'))), config('workspace.version'));
    }

    public function test_settings_show_both_versions_apart(): void
    {
        config(['workspace.version' => '9.9.9', 'version.app_version' => 'v1.2.3']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSeeInOrder([trans('admin/settings/general.workspace_version'), '9.9.9'])
            ->assertSeeInOrder([trans('admin/settings/general.snipe_version'), 'v1.2.3']);
    }
}
