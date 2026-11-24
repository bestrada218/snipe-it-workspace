<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Shows CHANGELOG.md: what each Snipe-IT Workspace version changed.
 */
class ChangelogController extends Controller
{
    public function __invoke(): View
    {
        $path = base_path('CHANGELOG.md');

        $markdown = is_readable($path) ? (string) file_get_contents($path) : '';

        // Only the versions, newest first: the numbering and release notes at
        // the top of the file are for whoever maintains the project.
        if (preg_match('/^## (?:Unreleased|\d).*$/m', $markdown, $match, PREG_OFFSET_CAPTURE)) {
            $markdown = substr($markdown, $match[0][1]);
        }

        // The file is ours, but raw HTML and unsafe links are dropped anyway.
        $html = $markdown !== ''
            ? Str::markdown($markdown, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ])
            : null;

        return view('changelog', ['html' => $html]);
    }
}
