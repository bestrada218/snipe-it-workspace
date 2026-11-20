<?php

namespace App\Http\Controllers;

use App\Services\AssetTagTypes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Settings > Asset Tags > Tag types: the PP codes offered by the tag builder.
 */
class AssetTagTypesController extends Controller
{
    public function index(): View
    {
        return view('settings.asset_tag_types', [
            'types' => AssetTagTypes::all(),
            'usage' => AssetTagTypes::usage(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rows = collect($request->input('types', []))
            ->map(fn ($row) => [
                'original' => strtoupper(trim((string) ($row['original'] ?? ''))) ?: null,
                'code' => strtoupper(trim((string) ($row['code'] ?? ''))),
                'name' => trim((string) ($row['name'] ?? '')),
                'suffix' => filter_var($row['suffix'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'remove' => filter_var($row['remove'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ])
            // Blank rows of the "add a type" area.
            ->reject(fn (array $row) => $row['original'] === null && $row['code'] === '' && $row['name'] === '')
            ->values();

        $request->merge(['types' => $rows->all()]);
        $request->validate([
            'types' => ['required', 'array'],
            'types.*.code' => ['exclude_if:types.*.remove,true', 'required', 'regex:'.AssetTagTypes::CODE_PATTERN],
            'types.*.name' => ['exclude_if:types.*.remove,true', 'required', 'string', 'max:60'],
        ], [], [
            'types.*.code' => trans('asset-tag-types.code'),
            'types.*.name' => trans('asset-tag-types.name'),
        ]);

        $codes = $rows->reject(fn (array $row) => $row['remove'])->pluck('code');
        $repeated = $codes->duplicates()->first();
        if ($repeated !== null) {
            throw ValidationException::withMessages(['types' => trans('asset-tag-types.repeated_code', ['code' => $repeated])]);
        }

        $changed = AssetTagTypes::save($rows->all(), $request->user());

        return redirect()->route('settings.asset_tag_types.index')
            ->with('success', trans_choice('asset-tag-types.saved', $changed, ['count' => $changed]));
    }
}
