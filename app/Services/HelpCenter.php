<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * The in-app help center: one Markdown file per section and language in
 * resources/help/<locale>/<section>.md (English when a translation is
 * missing). Each file starts with "# Title" and a short intro paragraph;
 * "##" and "###" headings make the section's own index.
 *
 * Links can point to a page of the app with route:<name>, for example
 * [Returns](route:returns.index).
 */
class HelpCenter
{
    /** Sections in reading order. */
    public const SECTIONS = [
        'getting-started',
        'asset-tags',
        'assignments',
        'shipments',
        'returns',
        'replacement',
        'damage-reports',
        'locations-and-inventory',
        'labels',
        'activity-and-history',
        'backups',
        'email-and-summary',
        'security',
        'server-and-updates',
    ];

    public const FALLBACK_LOCALE = 'en-US';

    /**
     * Every section with its title, intro and first-level headings (the
     * index of the help center).
     *
     * @return array<int, array{slug: string, title: string, intro: string, toc: array<int, array{id: string, text: string, level: int}>}>
     */
    public static function sections(): array
    {
        return array_values(array_filter(array_map(
            fn (string $slug) => self::section($slug),
            self::SECTIONS
        )));
    }

    /**
     * @return array{slug: string, title: string, intro: string, html: string, toc: array<int, array{id: string, text: string, level: int}>}|null
     */
    public static function section(string $slug): ?array
    {
        if (! in_array($slug, self::SECTIONS, true)) {
            return null;
        }

        $markdown = self::read($slug);
        if ($markdown === null) {
            return null;
        }

        $title = preg_match('/^# (.+)$/m', $markdown, $match) ? trim($match[1]) : Str::headline($slug);
        $body = trim(preg_replace('/^# .+$/m', '', $markdown, 1));
        $intro = trim(strtok($body, "\n\n") ?: '');

        $html = Str::markdown(self::resolveRoutes($body), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        // Give every ## / ### heading an id, and collect them as the index.
        $toc = [];
        $used = [];
        $html = preg_replace_callback('/<h([23])>(.*?)<\/h\1>/s', function (array $heading) use (&$toc, &$used) {
            $text = trim(html_entity_decode(strip_tags($heading[2]), ENT_QUOTES | ENT_HTML5));
            $id = Str::slug($text) ?: 'section';
            $base = $id;
            for ($n = 2; isset($used[$id]); $n++) {
                $id = $base.'-'.$n;
            }
            $used[$id] = true;
            $toc[] = ['id' => $id, 'text' => $text, 'level' => (int) $heading[1]];

            return '<h'.$heading[1].' id="'.$id.'">'.$heading[2].'<a class="help-anchor" href="#'.$id.'" aria-hidden="true">#</a></h'.$heading[1].'>';
        }, $html);

        return [
            'slug' => $slug,
            'title' => $title,
            'intro' => strip_tags(Str::markdown(self::resolveRoutes($intro))),
            'html' => $html,
            'toc' => $toc,
        ];
    }

    private static function read(string $slug): ?string
    {
        foreach (array_unique([app()->getLocale(), self::FALLBACK_LOCALE]) as $locale) {
            $path = resource_path("help/{$locale}/{$slug}.md");
            if (is_readable($path)) {
                return (string) file_get_contents($path);
            }
        }

        return null;
    }

    /**
     * [text](route:name) links to a page of the app and [text](help:section)
     * to another help section; both can end in #anchor.
     */
    private static function resolveRoutes(string $markdown): string
    {
        return preg_replace_callback('/\]\((route|help):([a-z0-9._\/-]+)(#[a-z0-9-]+)?\)/i', function (array $link) {
            $url = match (true) {
                $link[1] === 'help' && in_array($link[2], self::SECTIONS, true) => route('help.show', $link[2]),
                $link[1] === 'route' && Route::has($link[2]) => route($link[2]),
                default => '#',
            };

            return ']('.$url.($link[3] ?? '').')';
        }, $markdown);
    }
}
