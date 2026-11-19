<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\AssetReplacement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AssetReplacementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('index', Asset::class);

        $windows = AssetReplacement::windows();
        $selected = $request->query('window', 'all');
        abort_unless(is_string($selected) && ($selected === 'all' || array_key_exists($selected, $windows)), 404);

        $assets = AssetReplacement::query($windows[$selected] ?? null)
            ->with('location')
            ->orderBy('replacement_due_on')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('hardware.replacement-alerts', compact('assets', 'windows', 'selected'));
    }
}
