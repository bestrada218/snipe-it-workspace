<?php

namespace App\Services;

use App\Enums\ActionType;
use App\Enums\FileStorage;
use App\Helpers\Helper;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\AssetShipment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

final class AssetShipments
{
    public static function visibleTo(User $user): Builder
    {
        return AssetShipment::query()
            ->whereHas('asset')
            ->when(! Gate::forUser($user)->any(['index', 'checkout'], Asset::class),
                fn (Builder $query) => $query->where('recipient_id', $user->id));
    }

    public static function dueFor(User $user): Builder
    {
        return self::visibleTo($user)->open()->whereNull('received_at')->whereDate('expected_on', '<=', today());
    }

    public static function purchaseApprovers(): Collection
    {
        return User::whereKey(Setting::getSettings()?->purchase_approvers ?? [])
            ->orderBy('first_name')->orderBy('last_name')->get();
    }

    public static function rules(): array
    {
        return [
            'recipient_id' => ['required', 'integer'],
            'platform' => ['required', 'string', 'max:120'],
            'carrier' => ['nullable', 'string', 'max:120'],
            'tracking_number' => ['required', 'string', 'max:150'],
            'expected_on' => ['required', 'date_format:Y-m-d'],
            'purchase_approver_id' => ['required', 'integer', Rule::in(self::purchaseApprovers()->modelKeys())],
            'approval_reference' => ['required', 'string', 'max:1500'],
            'approval' => ['required', 'file', 'extensions:pdf,jpg,jpeg,png,webp', 'mimes:pdf,jpg,jpeg,png,webp', 'max:'.(int) ceil(Helper::file_upload_max_size() / 1024)],
        ];
    }

    public static function create(Asset $asset, User $recipient, User $actor, array $validated): AssetShipment
    {
        $approver = self::purchaseApprovers()->find((int) ($validated['purchase_approver_id'] ?? 0))
            ?? throw ValidationException::withMessages([
                'purchase_approver_id' => trans('asset-shipments.invalid_approver'),
            ]);
        $file = $validated['approval'];
        $path = FileStorage::AssetShipmentApprovals->privateStorageKey();
        $key = Str::uuid().'.'.$file->guessExtension();

        try {
            return DB::transaction(function () use ($asset, $recipient, $actor, $validated, $approver, $file, $path, $key): AssetShipment {
                $asset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
                Gate::forUser($actor)->authorize('checkout', $asset);
                if (! $asset->availableForCheckout() || ! $asset->canCheckoutTo($recipient)) {
                    self::unavailable();
                }

                if (! Storage::putFileAs($path, $file, $key)) {
                    throw ValidationException::withMessages(['approval' => trans('asset-shipments.upload_failed')]);
                }

                $shipment = $asset->shipments()->create([
                    'recipient_id' => $recipient->id,
                    'created_by' => $actor->id,
                    'platform' => $validated['platform'],
                    'carrier' => $validated['carrier'] ?? null,
                    'tracking_number' => $validated['tracking_number'],
                    'purchase_approver_id' => $approver->id,
                    'purchase_approver_name' => $approver->display_name,
                    'approval_reference' => $validated['approval_reference'],
                    'approval_key' => $key,
                    'approval_filename' => mb_substr(preg_replace('/[\x00-\x1f\x7f]/u', '', $file->getClientOriginalName()) ?: $key, 0, 255),
                    'expected_on' => $validated['expected_on'],
                ]);

                self::log($shipment, $actor, ActionType::ShipmentCreated);

                return $shipment;
            });
        } catch (Throwable $error) {
            Storage::delete($path.$key);
            throw $error;
        }
    }

    public static function receive(AssetShipment $shipment, Request $request): AssetShipment
    {
        return DB::transaction(function () use ($shipment, $request): AssetShipment {
            $asset = Asset::whereKey($shipment->asset_id)->lockForUpdate()->firstOrFail();
            $shipment = AssetShipment::whereKey($shipment->id)->lockForUpdate()->firstOrFail();
            $actor = $request->user();
            $isRecipient = (int) $shipment->recipient_id === (int) $actor->id;
            if (! $isRecipient) {
                Gate::forUser($actor)->authorize('checkout', $asset);
            }
            abort_if($shipment->cancelled_at, 409);
            if ($shipment->received_at) {
                return $shipment;
            }

            $receiptNote = $isRecipient ? null : $request->validate([
                'receipt_note' => ['required', 'string', 'max:1000'],
            ], [], ['receipt_note' => trans('asset-shipments.receipt_note')])['receipt_note'];

            $recipient = User::whereKey($shipment->recipient_id)->lockForUpdate()->firstOrFail();
            $shipment->received_at = now();
            $shipment->saveOrFail();
            self::log($shipment, $actor, ActionType::ShipmentReceived, $receiptNote);

            if (! $asset->assigned_to && $asset->canCheckoutTo($recipient)
                && $asset->status?->deployable && ! $asset->status?->archived
                && ! DuplicateAssignments::hasCurrentAssetAssignment($asset, $recipient)) {
                self::assign($shipment, $asset, $recipient, $actor);
            }

            return $shipment;
        });
    }

    public static function complete(AssetShipment $shipment, Request $request): AssetShipment
    {
        return DB::transaction(function () use ($shipment, $request): AssetShipment {
            $asset = Asset::whereKey($shipment->asset_id)->lockForUpdate()->firstOrFail();
            $shipment = AssetShipment::whereKey($shipment->id)->lockForUpdate()->firstOrFail();
            $actor = $request->user();
            Gate::forUser($actor)->authorize('checkout', $asset);
            abort_unless($shipment->isOpen() && $shipment->received_at, 409);
            $recipient = User::whereKey($shipment->recipient_id)->lockForUpdate()->firstOrFail();

            if ($asset->assigned_to || ! $asset->canCheckoutTo($recipient) || ! $asset->status?->deployable || $asset->status?->archived) {
                self::unavailable();
            }

            DuplicateAssignments::confirm($request, $asset, $recipient);
            self::assign($shipment, $asset, $recipient, $actor);

            return $shipment;
        });
    }

    public static function reschedule(AssetShipment $shipment, User $actor, string $date, string $reason): void
    {
        DB::transaction(function () use ($shipment, $actor, $date, $reason): void {
            $asset = Asset::whereKey($shipment->asset_id)->lockForUpdate()->firstOrFail();
            $shipment = AssetShipment::whereKey($shipment->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $shipment->recipient_id === (int) $actor->id || Gate::forUser($actor)->allows('checkout', $asset), 403);
            abort_unless($shipment->isOpen() && ! $shipment->received_at, 409);
            $old = $shipment->expected_on->toDateString();
            if ($old === $date) {
                return;
            }

            $shipment->expected_on = $date;
            $shipment->saveOrFail();
            self::log($shipment, $actor, ActionType::ShipmentRescheduled, $reason, [
                'shipment_expected_on' => ['old' => $old, 'new' => $date],
            ]);
        });
    }

    public static function cancel(AssetShipment $shipment, User $actor, string $reason): void
    {
        DB::transaction(function () use ($shipment, $actor, $reason): void {
            $asset = Asset::whereKey($shipment->asset_id)->lockForUpdate()->firstOrFail();
            $shipment = AssetShipment::whereKey($shipment->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('checkout', $asset);
            abort_unless($shipment->isOpen() && ! $shipment->received_at, 409);
            $shipment->cancelled_at = now();
            $shipment->saveOrFail();
            self::log($shipment, $actor, ActionType::ShipmentCancelled, $reason);
        });
    }

    private static function assign(AssetShipment $shipment, Asset $asset, User $recipient, User $actor): void
    {
        $shipment->assigned_at = now();
        $shipment->saveOrFail();

        if (! $asset->checkOut($recipient, $actor, now()->toDateTimeString(), null, trans('asset-shipments.history_checkout'), $asset->name)) {
            self::unavailable();
        }
    }

    private static function log(AssetShipment $shipment, User $actor, ActionType $action, ?string $note = null, array $changes = []): void
    {
        $log = new Actionlog;
        $log->item_type = Asset::class;
        $log->item_id = $shipment->asset_id;
        $log->target_type = User::class;
        $log->target_id = $shipment->recipient_id;
        $log->created_by = $actor->id;
        $log->company_id = $shipment->asset->company_id;
        $log->location_id = $shipment->asset->location_id;
        $log->note = $note;
        $log->log_meta = json_encode($changes + ['_shipment' => [
            'id' => $shipment->id,
            'tracking' => $shipment->tracking_number,
            'platform' => $shipment->platform,
            'expected_on' => $shipment->expected_on->toDateString(),
        ]], JSON_THROW_ON_ERROR);

        if (! $log->logaction($action)) {
            self::unavailable();
        }
    }

    private static function unavailable(): never
    {
        throw ValidationException::withMessages(['asset' => trans('asset-shipments.unavailable')]);
    }
}
