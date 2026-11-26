<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\AssetReturnItem;
use App\Models\User;
use App\Services\AssetReturns;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Returns of what a person holds (termination or change): the items, where
 * each one goes, the box shipment to the partner warehouse and the notes.
 */
class AssetReturnController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('index', Asset::class);
        $view = in_array($request->input('view'), ['closed', 'stalled'], true) ? $request->input('view') : 'open';

        $returns = AssetReturn::query()
            ->when($view === 'open', fn ($query) => $query->open()->orderByDesc('id'))
            ->when($view === 'stalled', fn ($query) => $query->stalled()->orderBy('updated_at'))
            ->when($view === 'closed', fn ($query) => $query->where(fn ($closed) => $closed->whereNotNull('completed_at')->orWhereNotNull('cancelled_at'))
                ->orderByRaw('COALESCE(completed_at, cancelled_at) DESC'))
            ->with(['person', 'returnOwner', 'items.assetReturn', 'notes'])
            ->withCount('items')
            ->paginate(25)->withQueryString();

        $stalledCount = AssetReturn::stalled()->count();

        return view('asset-returns.index', compact('returns', 'view', 'stalledCount'));
    }

    public function create(Request $request): View
    {
        $this->authorize('checkin', Asset::class);
        $person = $request->filled('user_id') ? User::find($request->integer('user_id')) : null;

        return view('asset-returns.create', [
            'person' => $person,
            'items' => $person ? AssetReturns::returnableItems($person) : collect(),
            'requestedAssetId' => $request->integer('asset_id'),
            'ownWarehouseTree' => AssetReturns::ownWarehouseTree(),
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
            'canUpdate' => $this->canUpdate($return),
            'ownWarehouseTree' => AssetReturns::ownWarehouseTree(),
            'partner' => AssetReturns::partnerName(),
        ]);
    }

    /** Mark a shipping step (the next one, or a later one) on a date. */
    public function advance(Request $request, AssetReturn $return): RedirectResponse
    {
        $this->authorizeUpdating($return);
        $input = $request->validate([
            'step' => ['nullable', Rule::in(AssetReturn::SHIPPING_STEPS)],
            'date' => ['nullable', 'date'],
        ]);
        AssetReturns::markShippingStep($return, $request->user(), $input['step'] ?? null, $this->date($input));

        return redirect()->route('returns.show', $return)->with('success', trans('asset-returns.step_saved'));
    }

    public function undo(AssetReturn $return): RedirectResponse
    {
        $this->authorizeUpdating($return);
        AssetReturns::undoShippingStep($return);

        return redirect()->route('returns.show', $return)->with('success', trans('asset-returns.step_undone'));
    }

    public function advanceItem(Request $request, AssetReturn $return, AssetReturnItem $item): RedirectResponse
    {
        $this->authorizeUpdating($return);
        abort_unless($item->asset_return_id === $return->id, 404);
        $input = $request->validate([
            'step' => ['nullable', Rule::in(AssetReturnItem::PARTNER_STEPS)],
            'date' => ['nullable', 'date'],
        ]);
        AssetReturns::markPartnerStep($item, $input['step'] ?? null, $this->date($input));

        return redirect()->route('returns.show', $return)->with('success', trans('asset-returns.step_saved'));
    }

    public function undoItem(AssetReturn $return, AssetReturnItem $item): RedirectResponse
    {
        $this->authorizeUpdating($return);
        abort_unless($item->asset_return_id === $return->id, 404);
        AssetReturns::undoPartnerStep($item);

        return redirect()->route('returns.show', $return)->with('success', trans('asset-returns.step_undone'));
    }

    public function storeItem(Request $request, AssetReturn $return, AssetReturnItem $item): RedirectResponse
    {
        $this->authorizeUpdating($return);
        abort_unless($item->asset_return_id === $return->id, 404);
        $input = $request->validate(['date' => ['nullable', 'date']]);
        AssetReturns::store($item, $request->user(), $this->date($input));

        return redirect()->route('returns.show', $return)->with('success', trans('asset-returns.stored', ['location' => $item->location?->name]));
    }

    public function updateDetails(Request $request, AssetReturn $return): RedirectResponse
    {
        $this->authorizeUpdating($return);
        AssetReturns::updateDetails($return, $request->validate(AssetReturns::detailRules()));

        return redirect()->route('returns.show', $return)->with('success', trans('asset-returns.details_saved'));
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function date(array $input): ?Carbon
    {
        return filled($input['date'] ?? null) ? Carbon::parse($input['date']) : null;
    }

    public function updateItem(Request $request, AssetReturn $return, AssetReturnItem $item): RedirectResponse
    {
        $this->authorizeUpdating($return);
        abort_unless($item->asset_return_id === $return->id, 404);
        AssetReturns::updateItem($item, $request->only(['destination', 'location_id', 'previously_with_partner']));

        return redirect()->route('returns.show', $return)->with('success', trans('asset-returns.item_saved'));
    }

    public function addNote(Request $request, AssetReturn $return): RedirectResponse
    {
        $this->authorizeUpdating($return);
        $input = $request->validate(['note' => ['required', 'string', 'max:5000']]);
        AssetReturns::addNote($return, $input['note'], $request->user());

        return redirect()->route('returns.show', $return)->withFragment('notes')->with('success', trans('asset-returns.note_saved'));
    }

    public function cancel(AssetReturn $return): RedirectResponse
    {
        $this->authorizeUpdating($return);
        AssetReturns::cancel($return);

        return redirect()->route('returns.show', $return)->with('success', trans('asset-returns.cancelled'));
    }

    private function authorizeUpdating(AssetReturn $return): void
    {
        abort_unless($this->canUpdate($return), 403);
    }

    private function canUpdate(AssetReturn $return): bool
    {
        return Gate::allows('checkin', Asset::class)
            && (Gate::allows('checkin', Accessory::class) || ! $return->items()->where('item_type', Accessory::class)->exists());
    }
}
