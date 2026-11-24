<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\AssetReturnItem;
use App\Models\User;
use App\Services\AssetReturns;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Returns of what a person holds (termination or change): the items, where
 * each one goes, the box shipment to the partner warehouse and the notes.
 */
class AssetReturnController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('index', Asset::class);
        $view = $request->input('view') === 'closed' ? 'closed' : 'open';

        $returns = AssetReturn::query()
            ->when($view === 'open', fn ($query) => $query->open()->orderByDesc('id'))
            ->when($view === 'closed', fn ($query) => $query->where(fn ($closed) => $closed->whereNotNull('completed_at')->orWhereNotNull('cancelled_at'))
                ->orderByRaw('COALESCE(completed_at, cancelled_at) DESC'))
            ->with(['person', 'returnOwner', 'items.assetReturn'])
            ->withCount('items')
            ->paginate(25)->withQueryString();

        return view('asset-returns.index', compact('returns', 'view'));
    }

    public function create(Request $request): View
    {
        $this->authorize('checkin', Asset::class);
        $person = $request->filled('user_id') ? User::find($request->integer('user_id')) : null;

        return view('asset-returns.create', [
            'person' => $person,
            'items' => $person ? AssetReturns::returnableItems($person) : collect(),
            'storageLocations' => AssetReturns::storageLocations(),
            'partner' => AssetReturns::partnerName(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('checkin', Asset::class);
        $input = $request->validate(AssetReturns::rules());

        $return = AssetReturns::create(User::findOrFail($input['user_id']), $input, $request->user());

        return redirect()->route('returns.show', $return)->with('success', trans('asset-returns.created'));
    }

    public function show(AssetReturn $return): View
    {
        $this->authorize('index', Asset::class);
        $return->load(['person', 'ticketOwner', 'returnOwner', 'items.item', 'items.location', 'items.assetReturn', 'notes.author']);

        return view('asset-returns.show', [
            'return' => $return,
            'storageLocations' => AssetReturns::storageLocations(),
            'partner' => AssetReturns::partnerName(),
        ]);
    }

    public function advance(Request $request, AssetReturn $return): RedirectResponse
    {
        $this->authorize('checkin', Asset::class);
        AssetReturns::advanceShipping($return, $request->user());

        return redirect()->route('returns.show', $return)->with('success', trans('asset-returns.step_saved'));
    }

    public function advanceItem(AssetReturn $return, AssetReturnItem $item): RedirectResponse
    {
        $this->authorize('checkin', Asset::class);
        abort_unless($item->asset_return_id === $return->id, 404);
        AssetReturns::advancePartnerStep($item);

        return redirect()->route('returns.show', $return)->with('success', trans('asset-returns.step_saved'));
    }

    public function storeItem(Request $request, AssetReturn $return, AssetReturnItem $item): RedirectResponse
    {
        $this->authorize('checkin', Asset::class);
        abort_unless($item->asset_return_id === $return->id, 404);
        AssetReturns::store($item, $request->user());

        return redirect()->route('returns.show', $return)->with('success', trans('asset-returns.stored', ['location' => $item->location?->name]));
    }

    public function updateItem(Request $request, AssetReturn $return, AssetReturnItem $item): RedirectResponse
    {
        $this->authorize('checkin', Asset::class);
        abort_unless($item->asset_return_id === $return->id, 404);
        AssetReturns::updateItem($item, $request->only(['destination', 'location_id', 'previously_with_partner']));

        return redirect()->route('returns.show', $return)->with('success', trans('asset-returns.item_saved'));
    }

    public function addNote(Request $request, AssetReturn $return): RedirectResponse
    {
        $this->authorize('checkin', Asset::class);
        $input = $request->validate(['note' => ['required', 'string', 'max:5000']]);
        AssetReturns::addNote($return, $input['note'], $request->user());

        return redirect()->route('returns.show', $return)->withFragment('notes')->with('success', trans('asset-returns.note_saved'));
    }

    public function cancel(AssetReturn $return): RedirectResponse
    {
        $this->authorize('checkin', Asset::class);
        AssetReturns::cancel($return);

        return redirect()->route('returns.show', $return)->with('success', trans('asset-returns.cancelled'));
    }
}
