<?php

namespace App\Services;

use App\Events\CheckoutableCheckedIn;
use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\AssetReturnItem;
use App\Models\LicenseSeat;
use App\Models\Location;
use App\Models\Setting;
use App\Models\Statuslabel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Returns of what a person holds: which items, where each one goes, the box
 * shipment back to the partner warehouse, storing items in a storage
 * location, and notes that are copied to every returned item's history.
 */
final class AssetReturns
{
    public static function partnerName(): string
    {
        return Setting::getSettings()?->return_partner_name ?: trans('asset-returns.partner_default');
    }

    public static function partnerLocation(): ?Location
    {
        $id = Setting::getSettings()?->return_partner_location_id;

        return $id ? Location::find($id) : null;
    }

    /**
     * @return Collection<int, Location>
     */
    public static function storageLocations(): Collection
    {
        return Location::where('is_storage', true)->orderBy('name')->get();
    }

    /**
     * Location hierarchy for choosing internal warehouses at the deepest
     * available level.
     *
     * @return Collection<int, array{location: Location, depth: int, selectable: bool}>
     */
    public static function ownWarehouseTree(): Collection
    {
        $locations = Location::query()->orderBy('name')->get(['id', 'name', 'parent_id', 'is_external']);
        $ids = $locations->pluck('id')->all();
        $byParent = $locations->groupBy(fn (Location $location) => in_array($location->parent_id, $ids, true) ? $location->parent_id : 0);
        $partnerId = self::partnerLocation()?->id;
        $rows = collect();

        $walk = function (int $parentId, int $depth) use (&$walk, $byParent, $partnerId, $rows): void {
            foreach ($byParent->get($parentId, collect()) as $location) {
                $rows->push([
                    'location' => $location,
                    'depth' => $depth,
                    'selectable' => ! $byParent->has($location->id) && ! $location->is_external && $location->id !== $partnerId,
                ]);
                $walk($location->id, $depth + 1);
            }
        };
        $walk(0, 0);

        // Even when an upper level has no children, it remains ineligible
        // if another branch goes deeper.
        $deepest = $rows->where('selectable', true)->max('depth') ?? 0;

        return $rows->map(fn (array $row) => ['selectable' => $row['selectable'] && $row['depth'] === $deepest] + $row);
    }

    /**
     * The locations that can keep returned items (see ownWarehouseTree()).
     *
     * @return Collection<int, Location>
     */
    public static function ownWarehouseLocations(): Collection
    {
        return self::ownWarehouseTree()->where('selectable', true)->pluck('location')->values();
    }

    public static function warehouseEditOptions(): Collection
    {
        $partner = self::partnerLocation();
        $options = self::ownWarehouseLocations()->keyBy('id')
            ->union(self::storageLocations()->keyBy('id'))
            ->map(fn (Location $location) => $location->name)
            ->sort(SORT_NATURAL | SORT_FLAG_CASE);

        if ($partner) {
            $options->put($partner->id, trans('asset-returns.destinations.partner', ['partner' => self::partnerName()]));
        }

        return $options;
    }

    /** @return array{location_id?: int, warehouse_location_id?: int} */
    public static function warehouseEditAttributes(Request $request): array
    {
        if (! $request->filled('warehouse_location_id')) {
            return [];
        }

        $validated = $request->validate([
            'warehouse_location_id' => ['integer', Rule::in(self::warehouseEditOptions()->keys()->all())],
        ], [
            'warehouse_location_id.in' => trans('asset-returns.warehouse_edit_invalid'),
        ]);
        $id = (int) $validated['warehouse_location_id'];

        return ['location_id' => $id, 'warehouse_location_id' => $id];
    }

    public static function requireUnassignedForWarehouseEdit(iterable $assets): void
    {
        foreach ($assets as $asset) {
            if (filled($asset->assigned_to)) {
                throw ValidationException::withMessages(['warehouse_location_id' => trans('asset-returns.warehouse_edit_assigned')]);
            }
        }
    }

    /**
     * Assets and accessories the person holds that are not already part of
     * an open return.
     *
     * @return Collection<int, array{key: string, type: string, id: int, checkout_id: ?int, tag: string, name: string, category: ?string}>
     */
    public static function returnableItems(User $person): Collection
    {
        $pending = AssetReturnItem::whereHas('assetReturn', fn ($query) => $query->withoutGlobalScopes([AssetReturn::COMPANY_SCOPE, AssetReturn::ACCESSORY_SCOPE])
            ->open())->get(['item_type', 'item_id', 'accessory_checkout_id']);
        $pendingAssets = $pending->where('item_type', Asset::class)->pluck('item_id')->all();
        $pendingCheckouts = $pending->where('item_type', Accessory::class)->pluck('accessory_checkout_id')->all();

        $assets = Asset::where('assigned_type', User::class)->where('assigned_to', $person->id)
            ->whereNotIn('id', $pendingAssets)->with('model.category')->orderBy('asset_tag')->get()
            ->map(fn (Asset $asset) => [
                'key' => 'asset-'.$asset->id,
                'type' => 'asset',
                'id' => $asset->id,
                'checkout_id' => null,
                'tag' => (string) $asset->asset_tag,
                'name' => (string) ($asset->name ?: $asset->model?->name),
                'category' => $asset->model?->category?->name,
            ]);

        if (Gate::denies('checkin', Accessory::class) || Gate::denies('index', Accessory::class)) {
            return $assets->values();
        }

        $accessories = AccessoryCheckout::where('assigned_type', User::class)->where('assigned_to', $person->id)
            ->whereNotIn('id', $pendingCheckouts)->with('accessory.category')->get()
            ->filter(fn (AccessoryCheckout $checkout) => $checkout->accessory !== null)
            ->map(fn (AccessoryCheckout $checkout) => [
                'key' => 'accessory-'.$checkout->id,
                'type' => 'accessory',
                'id' => $checkout->accessory->id,
                'checkout_id' => $checkout->id,
                'tag' => (string) $checkout->accessory->asset_tag,
                'name' => (string) $checkout->accessory->name,
                'category' => $checkout->accessory->category?->name,
            ]);

        return $assets->concat($accessories)->values();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function create(User $person, array $input, User $actor): AssetReturn
    {
        $returnable = self::returnableItems($person)->keyBy('key');
        $chosen = collect($input['items'] ?? [])->filter(fn ($item) => ! empty($item['include']));

        if ($chosen->isEmpty()) {
            throw ValidationException::withMessages(['items' => trans('asset-returns.choose_items')]);
        }

        $rows = $chosen->map(function (array $item, string $key) use ($returnable) {
            $found = $returnable->get($key) ?? throw ValidationException::withMessages(['items' => trans('asset-returns.item_unavailable')]);

            return self::itemRow($found['type'] === 'asset' ? Asset::class : Accessory::class, $found['id'], $found['checkout_id'], $item, 'items.'.$key);
        });

        return DB::transaction(function () use ($person, $input, $actor, $rows) {
            $return = AssetReturn::create([
                'user_id' => $person->id,
                'reason' => $input['reason'],
                'ticket_number' => $input['ticket_number'] ?? null,
                'ticket_owner_id' => $input['ticket_owner_id'] ?? null,
                'return_owner_id' => $input['return_owner_id'] ?? null,
                'created_by' => $actor->id,
            ]);
            $return->items()->createMany($rows->values()->all());

            if (filled($input['note'] ?? null)) {
                self::addNote($return, $input['note'], $actor);
            }

            return $return;
        });
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{item_type: string, item_id: int, accessory_checkout_id: ?int, destination: string, location_id: ?int, previously_with_partner: ?bool}
     */
    private static function itemRow(string $type, int $id, ?int $checkoutId, array $item, string $errorKey): array
    {
        $destination = $item['destination'] ?? null;
        if (! in_array($destination, AssetReturnItem::DESTINATIONS, true)) {
            throw ValidationException::withMessages([$errorKey => trans('asset-returns.choose_destination')]);
        }

        $locationId = null;
        $previously = null;
        if ($destination === 'storage') {
            $locationId = (int) ($item['location_id'] ?? 0);
            if (! self::ownWarehouseLocations()->contains('id', $locationId)) {
                throw ValidationException::withMessages([$errorKey => trans('asset-returns.choose_storage', ['partner' => self::partnerName()])]);
            }
        } else {
            if (! in_array((string) ($item['previously_with_partner'] ?? ''), ['0', '1'], true)) {
                throw ValidationException::withMessages([$errorKey => trans('asset-returns.answer_previously', ['partner' => self::partnerName()])]);
            }
            $previously = (string) $item['previously_with_partner'] === '1';
        }

        return [
            'item_type' => $type,
            'item_id' => $id,
            'accessory_checkout_id' => $checkoutId,
            'destination' => $destination,
            'location_id' => $locationId,
            'previously_with_partner' => $previously,
        ];
    }

    /**
     * Change where an item goes while it has not been checked in yet.
     *
     * @param  array<string, mixed>  $input
     */
    public static function updateItem(AssetReturnItem $item, array $input): void
    {
        if (! $item->assetReturn->isOpen() || $item->isCheckedIn()) {
            throw ValidationException::withMessages(['item' => trans('asset-returns.item_locked')]);
        }

        $row = self::itemRow($item->item_type, $item->item_id, $item->accessory_checkout_id, $input, 'item');
        $item->update(['destination' => $row['destination'], 'location_id' => $row['location_id'], 'previously_with_partner' => $row['previously_with_partner']]);
    }

    /**
     * Mark a step of the box shipment (by default the next one) on a date.
     * The shipment is tracked on another platform, so any later step can be
     * marked: the earlier ones still open take the same date. When the boxes
     * reach the warehouse, the partner items are checked in from the person.
     */
    public static function markShippingStep(AssetReturn $return, User $actor, ?string $step = null, ?Carbon $date = null): void
    {
        DB::transaction(function () use ($return, $actor, $step, $date) {
            $return = AssetReturn::whereKey($return->id)->lockForUpdate()->firstOrFail();
            $step ??= $return->nextShippingStep();
            if (! $return->isOpen() || ! $return->hasPartnerItems() || ! in_array($step, AssetReturn::SHIPPING_STEPS, true) || $return->{$step} !== null) {
                throw ValidationException::withMessages(['step' => trans('asset-returns.step_not_available')]);
            }

            $date = self::stepDate($date, $return);
            foreach (AssetReturn::SHIPPING_STEPS as $each) {
                if ($return->{$each} === null) {
                    $return->{$each} = $date;
                }
                if ($each === $step) {
                    break;
                }
            }
            $return->save();

            if ($step === 'warehouse_arrived_at') {
                $location = self::partnerLocation();
                $return->items->filter->goesToPartner()->each(
                    fn (AssetReturnItem $item) => self::checkIn($item, $return, $actor, $location, trans('asset-returns.log_partner', ['partner' => self::partnerName(), 'id' => $return->id]))
                );
            }

            self::completeIfDone($return->fresh('items'));
        });
    }

    /** Kept for callers that only move to the next step today. */
    public static function advanceShipping(AssetReturn $return, User $actor): void
    {
        self::markShippingStep($return, $actor);
    }

    /**
     * Undo the last shipping step marked by mistake. The arrival cannot be
     * undone: it already checked the items in.
     */
    public static function undoShippingStep(AssetReturn $return): void
    {
        $step = $return->lastShippingStep();
        if (! $return->isOpen() || $step === null || $step === 'warehouse_arrived_at') {
            throw ValidationException::withMessages(['step' => trans('asset-returns.cannot_undo')]);
        }

        $return->{$step} = null;
        $return->save();
    }

    /**
     * Mark the partner received or set up an item that was not with them
     * before (by default the next of the two), on a date.
     */
    public static function markPartnerStep(AssetReturnItem $item, ?string $step = null, ?Carbon $date = null): void
    {
        DB::transaction(function () use ($item, $step, $date): void {
            $return = AssetReturn::whereKey($item->asset_return_id)->lockForUpdate()->firstOrFail();
            $item = AssetReturnItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $item->setRelation('assetReturn', $return);
            $step ??= $item->nextPartnerStep();
            if (! $return->isOpen() || $item->nextPartnerStep() === null || ! in_array($step, AssetReturnItem::PARTNER_STEPS, true) || $item->{$step} !== null) {
                throw ValidationException::withMessages(['step' => trans('asset-returns.step_not_available')]);
            }

            $date = self::stepDate($date, $return);
            foreach (AssetReturnItem::PARTNER_STEPS as $each) {
                if ($item->{$each} === null) {
                    $item->{$each} = $date;
                }
                if ($each === $step) {
                    break;
                }
            }
            if ($step === 'partner_configured_at') {
                self::transitionPartnerSetup($item, false);
            }
            $item->save();
            self::completeIfDone($return->fresh('items'));
        });
    }

    public static function advancePartnerStep(AssetReturnItem $item): void
    {
        self::markPartnerStep($item);
    }

    /** Undo the last partner step of an item; a completed return opens again. */
    public static function undoPartnerStep(AssetReturnItem $item): void
    {
        DB::transaction(function () use ($item): void {
            $return = AssetReturn::whereKey($item->asset_return_id)->lockForUpdate()->firstOrFail();
            $item = AssetReturnItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $item->setRelation('assetReturn', $return);
            $step = $item->lastPartnerStep();
            if ($return->cancelled_at !== null || $step === null) {
                throw ValidationException::withMessages(['step' => trans('asset-returns.cannot_undo')]);
            }

            if ($step === 'partner_configured_at') {
                self::transitionPartnerSetup($item, true);
            }
            $item->{$step} = null;
            $item->save();
            if ($return->completed_at !== null) {
                $return->completed_at = null;
                $return->save();
            }
        });
    }

    private static function workflowStatusId(string $key): int
    {
        $status = Statuslabel::where('workflow_key', $key)->first();
        $type = $key === 'ready' ? 'deployable' : 'pending';
        if (! $status || $status->getStatuslabelType() !== $type) {
            throw ValidationException::withMessages(['step' => trans('asset-returns.workflow_status_unavailable')]);
        }

        return (int) $status->id;
    }

    private static function transitionPartnerSetup(AssetReturnItem $item, bool $undo): void
    {
        if ($item->item_type !== Asset::class) {
            return;
        }

        $asset = Asset::whereKey($item->item_id)->lockForUpdate()->first();
        if (! $asset) {
            return;
        }
        if ($undo && filled($asset->assigned_to)) {
            throw ValidationException::withMessages(['step' => trans('asset-returns.cannot_undo_assigned_setup')]);
        }

        $from = self::workflowStatusId($undo ? 'ready' : 'setup');
        if (filled($asset->assigned_to) || (int) $asset->status_id !== $from
            || (int) $asset->location_id !== (int) self::partnerLocation()?->id
            || (int) AssetReturnItem::where('item_type', Asset::class)->where('item_id', $asset->id)->max('id') !== $item->id) {
            return;
        }

        $asset->status_id = self::workflowStatusId($undo ? 'setup' : 'ready');
        if (! $asset->save()) {
            throw ValidationException::withMessages($asset->getErrors()->toArray());
        }
    }

    /**
     * Ticket, owners, reason and the box shipment's carrier and tracking
     * number can be corrected while the return is open.
     *
     * @param  array<string, mixed>  $input
     */
    public static function updateDetails(AssetReturn $return, array $input): void
    {
        if ($return->cancelled_at !== null) {
            throw ValidationException::withMessages(['return' => trans('asset-returns.step_not_available')]);
        }

        $return->fill(collect($input)->only(['reason', 'ticket_number', 'ticket_owner_id', 'return_owner_id', 'carrier', 'tracking_number'])->all());
        $return->save();
    }

    /**
     * @return array<string, mixed>
     */
    public static function detailRules(): array
    {
        return [
            'reason' => ['required', Rule::in(AssetReturn::REASONS)],
            'ticket_number' => ['nullable', 'string', 'max:100'],
            'ticket_owner_id' => ['nullable', 'bail', 'integer', self::accessibleUser(...)],
            'return_owner_id' => ['nullable', 'bail', 'integer', self::accessibleUser(...)],
            'carrier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    private static function accessibleUser(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! User::whereKey($value)->exists()) {
            $fail('validation.exists')->translate();
        }
    }

    /**
     * The date someone typed for a step: not in the future, not before the
     * return was created. Today keeps the current time.
     */
    private static function stepDate(?Carbon $date, AssetReturn $return): Carbon
    {
        if ($date === null || $date->isToday()) {
            return now();
        }
        if ($date->isFuture() || $date->lt($return->created_at->copy()->startOfDay())) {
            throw ValidationException::withMessages(['step' => trans('asset-returns.invalid_date')]);
        }

        return $date->copy()->setTime(12, 0);
    }

    /** Check the item in from the person: back in stock at the receiving warehouse. */
    public static function store(AssetReturnItem $item, User $actor, ?Carbon $date = null): void
    {
        DB::transaction(function () use ($item, $actor, $date) {
            $item = AssetReturnItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $return = $item->assetReturn;
            if (! $return->isOpen() || $item->goesToPartner() || $item->stored_at !== null) {
                throw ValidationException::withMessages(['step' => trans('asset-returns.step_not_available')]);
            }

            self::checkIn($item, $return, $actor, $item->location, trans('asset-returns.log_stored', ['id' => $return->id]));
            $item->stored_at = self::stepDate($date, $return);
            $item->save();

            self::completeIfDone($return->fresh('items'));
        });
    }

    /** A note on the return is added to the history of every returned item. */
    public static function addNote(AssetReturn $return, string $note, User $actor): void
    {
        DB::transaction(function () use ($return, $note, $actor) {
            $return->notes()->create(['note' => $note, 'created_by' => $actor->id]);

            $return->loadMissing('items.item')->items->each(function (AssetReturnItem $item) use ($return, $note, $actor) {
                if (! $item->item) {
                    return;
                }
                $log = new Actionlog;
                $log->item_id = $item->item->id;
                $log->item_type = $item->item_type;
                $log->note = trans('asset-returns.note_prefix', ['id' => $return->id]).' '.$note;
                $log->created_by = $actor->id;
                $log->logaction('note added');
            });
        });
    }

    public static function cancel(AssetReturn $return): void
    {
        if (! $return->canBeCancelled()) {
            throw ValidationException::withMessages(['return' => trans('asset-returns.cannot_cancel')]);
        }

        $return->cancelled_at = now();
        $return->save();
    }

    private static function completeIfDone(AssetReturn $return): void
    {
        if ($return->isOpen() && $return->items->every(fn (AssetReturnItem $item) => $item->isDone())) {
            $return->completed_at = now();
            $return->save();
        }
    }

    /**
     * Check the item in from the person; with a location, assign it there
     * (stored, so never counted as an installed fixed asset).
     */
    private static function checkIn(AssetReturnItem $item, AssetReturn $return, User $actor, ?Location $location, string $note): void
    {
        $person = $return->person;

        if ($item->item_type === Asset::class) {
            $asset = Asset::whereKey($item->item_id)->lockForUpdate()->first();
            if (! $asset || (int) $asset->assigned_to !== (int) $person->id || $asset->assigned_type !== User::class) {
                return;
            }

            $original = $asset->getRawOriginal();
            $asset->expected_checkin = null;
            $asset->last_checkin = now();
            $asset->accepted = null;
            $asset->assignedTo()->dissociate();
            $asset->licenseseats->each(fn (LicenseSeat $seat) => $seat->update(['assigned_to' => null]));
            if ($location) {
                $asset->location_id = $location->id;
                $asset->warehouse_location_id = $location->id;
            }
            if ($asset->status?->getStatuslabelType() === 'deployable') {
                $key = $item->goesToPartner() ? ($item->needsPartnerSetup() ? 'setup' : 'ready') : 'review';
                $asset->status_id = self::workflowStatusId($key);
            }
            if (! $asset->save()) {
                throw ValidationException::withMessages($asset->getErrors()->toArray());
            }
            // Physical receipt and readiness for assignment are separate.
            event(new CheckoutableCheckedIn($asset, $person, $actor, $note, now()->format('Y-m-d H:i:s'), $original));

            return;
        }

        $accessory = Accessory::whereKey($item->item_id)->lockForUpdate()->first();
        $checkout = AccessoryCheckout::whereKey($item->accessory_checkout_id)->lockForUpdate()->first();
        if (! $accessory || ! $checkout || (int) $checkout->accessory_id !== (int) $accessory->id
            || $checkout->assigned_type !== User::class || (int) $checkout->assigned_to !== (int) $person->id) {
            return;
        }

        $checkout->delete();
        if ($location) {
            AccessoryStockLocations::putAway($accessory, $location);
        }
        event(new CheckoutableCheckedIn($accessory, $person, $actor, $note, now()->format('Y-m-d H:i:s')));
    }

    /**
     * The warehouse where a returned asset is waiting: the internal location it was stored
     * at, or the partner warehouse. Null once it was checked out again or
     * moved somewhere else.
     *
     * @return array{label: string, location: Location, return: AssetReturn}|null
     */
    public static function storageFor(Asset $asset): ?array
    {
        if ($asset->assigned_to || ! $asset->location_id) {
            return null;
        }

        $item = AssetReturnItem::query()
            ->where('item_type', Asset::class)
            ->where('item_id', $asset->id)
            ->with(['assetReturn', 'location'])
            ->latest('id')
            ->first();
        if (! $item || ! $item->assetReturn) {
            return null;
        }

        $partner = $item->goesToPartner();
        $since = $partner ? $item->assetReturn->warehouse_arrived_at : $item->stored_at;
        $location = $partner ? self::partnerLocation() : $item->location;
        if (! $since || ! $location || (int) $location->id !== (int) $asset->location_id) {
            return null;
        }
        if ($asset->last_checkout && Carbon::parse($asset->last_checkout)->greaterThan($since)) {
            return null;
        }

        return [
            'label' => $partner
                ? trans('asset-returns.in_warehouse', ['place' => self::partnerName()])
                : trans('asset-returns.in_warehouse', ['place' => $location->name]),
            'location' => $location,
            'return' => $item->assetReturn,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'user_id' => ['required', 'bail', 'integer', self::accessibleUser(...)],
            'reason' => ['required', Rule::in(AssetReturn::REASONS)],
            'ticket_number' => ['nullable', 'string', 'max:100'],
            'ticket_owner_id' => ['nullable', 'bail', 'integer', self::accessibleUser(...)],
            'return_owner_id' => ['nullable', 'bail', 'integer', self::accessibleUser(...)],
            'note' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array'],
        ];
    }
}
