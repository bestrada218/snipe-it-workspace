<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Renderer\HtmlRenderer;

/**
 * Shows CHANGELOG.md: what each Snipe-IT Workspace version changed.
 */
class ChangelogController extends Controller
{
    public function __invoke(): View
    {
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $environment = $converter->getEnvironment();
        $parser = new MarkdownParser($environment);
        $renderer = new HtmlRenderer($environment);
        $notesLocale = str_starts_with(app()->getLocale(), 'es-') ? 'es-CO' : 'en-US';
        $english = $this->readReleases(base_path('CHANGELOG.md'), $parser);
        $translated = $notesLocale === 'es-CO'
            ? $this->readReleases(resource_path('lang/es-CO/changelog.md'), $parser)
            : [];
        $releases = [];

        foreach ($english as $version => $release) {
            $releases[] = [
                'title' => str_starts_with($release['title'], 'Unreleased')
                    ? trans('general.workspace_changelog_unreleased', ['version' => $version], $notesLocale)
                    : $release['title'],
                ...$this->renderNotes($translated[$version]['blocks'] ?? $release['blocks'], $renderer),
            ];
        }

        return view('changelog', compact('releases', 'notesLocale'));
    }

    /** @return array<string, array{title: string, blocks: list<AbstractBlock>}> */
    private function readReleases(string $path, MarkdownParser $parser): array
    {
        if (! is_readable($path)) {
            return [];
        }

        $document = $parser->parse((string) file_get_contents($path));
        $releases = [];
        $version = null;

        foreach ($document->children() as $block) {
            if ($block instanceof Heading && $block->getLevel() === 2) {
                $heading = $block->firstChild();
                $title = $heading instanceof Text ? $heading->getLiteral() : '';
                $version = null;
                if (preg_match('/^(?:Unreleased \(next: )?(\d+(?:\.\d+)+)(?=$|[\s)])/u', $title, $match)) {
                    $version = $match[1];
                    $releases[$version] = ['title' => $title, 'blocks' => []];
                }

                continue;
            }

            if ($version !== null) {
                $releases[$version]['blocks'][] = $block;
            }
        }

        return $releases;
    }

    /**
     * @param  list<AbstractBlock>  $blocks
     * @return array{intro: string, topics: list<string>}
     */
    private function renderNotes(array $blocks, HtmlRenderer $renderer): array
    {
        $intro = '';
        $topics = [];

        foreach ($blocks as $block) {
            if ($block instanceof Heading && $block->getLevel() === 3) {
                $topics[] = '';
            }

            $topic = array_key_last($topics);
            $html = $renderer->renderNodes([$block])."\n";
            if ($topic === null) {
                $intro .= $html;
            } else {
                $topics[$topic] .= $html;
            }
        }

        return compact('intro', 'topics');
    }
}
