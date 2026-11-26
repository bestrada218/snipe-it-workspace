<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Tests\TestCase;

class ChangelogPageTest extends TestCase
{
    public function test_page_renders(): void
    {
        $this->actingAs(User::factory()->create(['locale' => 'en-US']))
            ->get(route('changelog'))
            ->assertOk()
            ->assertSee('Changes by version')
            ->assertSeeInOrder([
                '<article class="workspace-changelog-release">',
                '<h2>0.1',
                '<div class="workspace-changelog-topics">',
                '<section class="workspace-changelog-topic">',
                '<h3>Asset tags</h3>',
                '</section>',
                '<section class="workspace-changelog-topic">',
                '<h3>Assignments</h3>',
            ], false)
            ->assertDontSee('How versions are numbered');
    }

    public function test_page_follows_the_users_spanish_locale(): void
    {
        $this->actingAs(User::factory()->create(['locale' => 'es-MX']))
            ->get(route('changelog'))
            ->assertOk()
            ->assertSee('Cambios por versión')
            ->assertSeeInOrder([
                '<h3>Bodegas e inventario</h3>',
                'La vista Bodegas incluye gráficos de existencias',
                '<h2>0.1 — 2026-11-24</h2>',
                '<h3>Placas de activos</h3>',
                '<h3>Asignaciones</h3>',
            ], false)
            ->assertDontSee('Warehouses and inventory')
            ->assertDontSee('Asset tags');
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
