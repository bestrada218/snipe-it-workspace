<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Tests\TestCase;

class ChangelogPageTest extends TestCase
{
    public function test_page_renders(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('changelog'))
            ->assertOk()
            ->assertSee(trans('general.workspace_changelog'))
            ->assertSee('<h2>0.1', false)
            ->assertDontSee('How versions are numbered');
    }

    public function test_requires_permission(): void
    {
        User::factory()->create();

        $this->get(route('changelog'))->assertRedirect(route('login'));
    }

    public function test_footer_and_settings_link_to_it(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('href="'.route('changelog').'"', false);
    }
}
