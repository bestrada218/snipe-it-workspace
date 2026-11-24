<?php

namespace App\Services;

use App\Events\CheckoutableCheckedIn;
use App\Events\CheckoutableCheckedOut;
use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\AssetReturnItem;
use App\Models\LicenseSeat;
use App\Models\Location;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
     * Where items that do not go to the partner can be kept: any location
     * (each clinic), except the partner's own warehouse.
     */
    public static function clinicLocations(): Collection
    {
        return Location::query()
            ->when(self::partnerLocation(), fn ($query, Location $partner) => $query->whereKeyNot($partner->id))
            ->orderBy('name')
            ->get();
    }

    /**
     * Assets and accessories the person holds that are not already part of
     * an open return.
     *
     * @return Collection<int, array{key: string, type: string, id: int, checkout_id: ?int, tag: string, name: string, category: ?string}>
     */
    public static function returnableItems(User $person): Collection
    {
        $pending = AssetReturnItem::whereHas('assetReturn', fn ($query) => $query->open())->get(['item_type', 'item_id', 'accessory_checkout_id']);
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
            if (! self::clinicLocations()->contains('id', $locationId)) {
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
     * Mark the next step of the box shipment. When the boxes reach the
     * warehouse, the partner items are checked in from the person.
     */
    public static function advanceShipping(AssetReturn $return, User $actor): void
    {
        DB::transaction(function () use ($return, $actor) {
            $return = AssetReturn::whereKey($return->id)->lockForUpdate()->firstOrFail();
            $step = $return->nextShippingStep();
            if (! $return->isOpen() || $step === null || ! $return->hasPartnerItems()) {
                throw ValidationException::withMessages(['step' => trans('asset-returns.step_not_available')]);
            }

            $return->{$step} = now();
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

    /** Mark the partner received or set up one item that was not with them before. */
    public static function advancePartnerStep(AssetReturnItem $item): void
    {
        $step = $item->nextPartnerStep();
        if (! $item->assetReturn->isOpen() || $step === null) {
            throw ValidationException::withMessages(['step' => trans('asset-returns.step_not_available')]);
        }

        $item->{$step} = now();
        $item->save();
        self::completeIfDone($item->assetReturn->fresh('items'));
    }

    /** Check the item in from the person and assign it to its storage location. */
    public static function store(AssetReturnItem $item, User $actor): void
    {
        DB::transaction(function () use ($item, $actor) {
            $item = AssetReturnItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $return = $item->assetReturn;
            if (! $return->isOpen() || $item->goesToPartner() || $item->stored_at !== null) {
                throw ValidationException::withMessages(['step' => trans('asset-returns.step_not_available')]);
            }

            self::checkIn($item, $return, $actor, $item->location, trans('asset-returns.log_stored', ['id' => $return->id]));
            $item->stored_at = now();
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
            }
            $asset->save();
            event(new CheckoutableCheckedIn($asset, $person, $actor, $note, now()->format('Y-m-d H:i:s'), $original));

            if ($location) {
                $asset->checkOut($location, $actor, now()->format('Y-m-d H:i:s'), null, $note, $asset->name, $location->id);
            }

            return;
        }

        $checkout = AccessoryCheckout::find($item->accessory_checkout_id);
        $accessory = Accessory::find($item->item_id);
        if (! $checkout || ! $accessory) {
            return;
        }
        $checkout->delete();
        event(new CheckoutableCheckedIn($accessory, $person, $actor, $note, now()->format('Y-m-d H:i:s')));

        if ($location) {
            $stored = new AccessoryCheckout([
                'accessory_id' => $accessory->id,
                'assigned_to' => $location->id,
                'assigned_type' => Location::class,
                'note' => $note,
            ]);
            $stored->created_by = $actor->id;
            $stored->save();
            event(new CheckoutableCheckedOut($accessory, $location, $actor, $note));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'reason' => ['required', Rule::in(AssetReturn::REASONS)],
            'ticket_number' => ['nullable', 'string', 'max:100'],
            'ticket_owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'return_owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'note' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array'],
        ];
    }
}
