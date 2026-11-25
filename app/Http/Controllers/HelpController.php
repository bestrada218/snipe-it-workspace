<?php

namespace App\Http\Controllers;

use App\Services\HelpCenter;
use Illuminate\View\View;

/**
 * The help center: how every Snipe-IT Workspace feature works.
 */
class HelpController extends Controller
{
    public function index(): View
    {
        return view('help.index', ['sections' => HelpCenter::sections()]);
    }

    public function show(string $section): View
    {
        $current = HelpCenter::section($section);
        abort_if($current === null, 404);

        $sections = HelpCenter::sections();
        $position = array_search($section, array_column($sections, 'slug'), true);

        return view('help.show', [
            'section' => $current,
            'sections' => $sections,
            'previous' => $sections[$position - 1] ?? null,
            'next' => $sections[$position + 1] ?? null,
        ]);
    }
}
