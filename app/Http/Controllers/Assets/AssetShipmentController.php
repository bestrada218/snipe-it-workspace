<?php

namespace App\Http\Controllers\Assets;

use App\Enums\FileStorage;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetShipment;
use App\Models\User;
use App\Services\AssetShipments;
use App\Services\DuplicateAssignments;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetShipmentController extends Controller
{
    public function index(Request $request): View
    {
        $shipments = AssetShipments::visibleTo($request->user())
            ->when($request->boolean('due'), fn ($query) => $query->open()
                ->whereNull('received_at')->whereDate('expected_on', '<=', today()))
            ->when($request->integer('asset'), fn ($query, $id) => $query->where('asset_id', $id))
            ->with(['asset.model', 'recipient'])
            ->orderByRaw('CASE WHEN assigned_at IS NULL AND cancelled_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('expected_on')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('asset-shipments.index', compact('shipments'));
    }

    public function create(?Asset $asset = null): View
    {
        $this->authorize('checkout', $asset ?? Asset::class);
        abort_if($asset && ! $asset->availableForCheckout(), 409);

        return view('asset-shipments.create', compact('asset'));
    }

    public function store(Request $request, ?Asset $asset = null): RedirectResponse
    {
        $this->authorize('checkout', $asset ?? Asset::class);
        $data = $request->validate(AssetShipments::rules() + ($asset ? [] : ['asset_id' => ['required', 'integer']]));
        if (! $asset) {
            $asset = Asset::rtd()->find($data['asset_id']) ?? throw ValidationException::withMessages([
                'asset_id' => trans('asset-shipments.unavailable'),
            ]);
        }

        $recipient = User::findOrFail($data['recipient_id']);
        $shipment = AssetShipments::create($asset, $recipient, $request->user(), $data);

        return redirect()->route('shipments.show', $shipment)->with('success', trans('asset-shipments.saved'));
    }

    public function show(Request $request, AssetShipment $shipment): View
    {
        $this->authorizeViewing($request, $shipment);
        $shipment->load('recipient');
        $canManage = $request->user()->can('checkout', $shipment->asset);
        $canViewApproval = $this->canViewApproval($request, $shipment);
        $duplicateCheck = $canManage && $shipment->isOpen() && $shipment->received_at && $shipment->recipient
            ? DuplicateAssignments::inspect($shipment->asset, $shipment->recipient, 1)
            : null;
        $duplicate = (bool) ($duplicateCheck['duplicate'] ?? false);
        $duplicateNeedsApproval = $duplicate && $duplicateCheck['requires_approval'];

        return view('asset-shipments.show', compact('shipment', 'canManage', 'canViewApproval', 'duplicate', 'duplicateNeedsApproval'));
    }

    public function receive(Request $request, AssetShipment $shipment): RedirectResponse
    {
        $shipment = AssetShipments::receive($shipment, $request);

        return back()->with('success', trans($shipment->assigned_at ? 'asset-shipments.receipt_saved' : 'asset-shipments.receipt_pending'));
    }

    public function complete(Request $request, AssetShipment $shipment): RedirectResponse
    {
        AssetShipments::complete($shipment, $request);

        return back()->with('success', trans('asset-shipments.receipt_saved'));
    }

    public function reschedule(Request $request, AssetShipment $shipment): RedirectResponse
    {
        $data = $request->validate([
            'expected_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        AssetShipments::reschedule($shipment, $request->user(), $data['expected_on'], $data['reason']);

        return back()->with('success', trans('asset-shipments.schedule_saved'));
    }

    public function cancel(Request $request, AssetShipment $shipment): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        AssetShipments::cancel($shipment, $request->user(), $data['reason']);

        return back()->with('success', trans('asset-shipments.cancellation_saved'));
    }

    public function approval(Request $request, AssetShipment $shipment): StreamedResponse
    {
        abort_unless($this->canViewApproval($request, $shipment), 403);
        $path = FileStorage::AssetShipmentApprovals->privateStorageKey().$shipment->approval_key;
        abort_unless(Storage::exists($path), 404);

        return Storage::download($path, $shipment->approval_filename, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function authorizeViewing(Request $request, AssetShipment $shipment): void
    {
        abort_unless($shipment->asset && ((int) $request->user()->id === (int) $shipment->recipient_id
            || $request->user()->canAny(['view', 'checkout'], $shipment->asset)), 403);
    }

    private function canViewApproval(Request $request, AssetShipment $shipment): bool
    {
        return $shipment->asset && ($request->user()->can('checkout', $shipment->asset)
            || ($request->user()->can('view', $shipment->asset) && $request->user()->can('history', $shipment->asset)));
    }
}
