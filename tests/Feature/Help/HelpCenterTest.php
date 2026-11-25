<?php

namespace Tests\Feature\Help;

use App\Models\User;
use App\Services\HelpCenter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HelpCenterTest extends TestCase
{
    public function test_page_renders(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('help.index'))
            ->assertOk();

        foreach (HelpCenter::sections() as $section) {
            $response->assertSee($section['title'])->assertSee(route('help.show', $section['slug']));
        }
    }

    public function test_requires_permission(): void
    {
        User::factory()->create();

        $this->get(route('help.index'))->assertRedirect(route('login'));
        $this->get(route('help.show', 'returns'))->assertRedirect(route('login'));
    }

    public function test_each_section_has_its_own_index_with_anchors(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('help.show', 'returns'))
            ->assertOk()
            ->assertSee('id="recording-progress-by-hand"', false)
            ->assertSee('href="#recording-progress-by-hand"', false)
            ->assertSee(route('help.show', 'shipments'));
    }

    public function test_an_unknown_section_is_not_found(): void
    {
        $this->actingAs(User::factory()->create())->get(route('help.show', 'nothing-here'))->assertNotFound();
    }

    public function test_every_section_exists_in_every_language_and_its_links_resolve(): void
    {
        foreach (['en-US', 'es-CO'] as $locale) {
            foreach (HelpCenter::SECTIONS as $slug) {
                $path = resource_path("help/{$locale}/{$slug}.md");
                $this->assertFileExists($path);
                $markdown = file_get_contents($path);
                $this->assertMatchesRegularExpression('/^# .+/', $markdown, "{$locale}/{$slug} has no title");

                preg_match_all('/\]\((route|help):([a-z0-9._\/-]+)/i', $markdown, $links, PREG_SET_ORDER);
                foreach ($links as $link) {
                    $this->assertTrue(
                        $link[1] === 'route' ? Route::has($link[2]) : in_array($link[2], HelpCenter::SECTIONS, true),
                        "{$locale}/{$slug}: broken link {$link[1]}:{$link[2]}"
                    );
                }
            }
        }
    }

    public function test_links_to_anchors_of_other_sections_point_to_real_headings(): void
    {
        foreach (['en-US', 'es-CO'] as $locale) {
            app()->setLocale($locale);
            $sections = collect(HelpCenter::SECTIONS)->mapWithKeys(fn (string $slug) => [$slug => HelpCenter::section($slug)]);

            foreach ($sections as $slug => $section) {
                preg_match_all('/\]\(help:([a-z-]+)#([a-z0-9-]+)\)|\]\(#([a-z0-9-]+)\)/', file_get_contents(resource_path("help/{$locale}/{$slug}.md")), $links, PREG_SET_ORDER);
                foreach ($links as $link) {
                    [$target, $anchor] = ($link[1] ?? '') !== '' ? [$link[1], $link[2]] : [$slug, $link[3]];
                    $this->assertContains($anchor, array_column($sections[$target]['toc'], 'id'), "{$locale}/{$slug}: #{$anchor} not found in {$target}");
                }
            }
        }
    }
}
