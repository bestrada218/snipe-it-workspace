<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One returned item (asset or accessory) and where it goes: back to the
 * partner warehouse, or stored in a storage location.
 */
class AssetReturnItem extends Model
{
    public const DESTINATIONS = ['partner', 'storage'];

    protected $fillable = ['item_type', 'item_id', 'accessory_checkout_id', 'destination', 'location_id', 'previously_with_partner'];

    protected $casts = [
        'previously_with_partner' => 'boolean',
        'partner_received_at' => 'datetime',
        'partner_configured_at' => 'datetime',
        'stored_at' => 'datetime',
    ];

    public function assetReturn(): BelongsTo
    {
        return $this->belongsTo(AssetReturn::class);
    }

    public function item(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function goesToPartner(): bool
    {
        return $this->destination === 'partner';
    }

    /**
     * An item that was not with the partner before must be received and
     * set up by them (enrolled in their systems) once it arrives.
     */
    public function needsPartnerSetup(): bool
    {
        return $this->goesToPartner() && $this->previously_with_partner === false;
    }

    /** Has the item been checked in from the person? */
    public function isCheckedIn(): bool
    {
        return $this->goesToPartner() ? $this->assetReturn->warehouse_arrived_at !== null : $this->stored_at !== null;
    }

    public function isDone(): bool
    {
        if (! $this->goesToPartner()) {
            return $this->stored_at !== null;
        }

        return $this->assetReturn->warehouse_arrived_at !== null
            && (! $this->needsPartnerSetup() || $this->partner_configured_at !== null);
    }

    /** Next per-item step for partner items: partner_received_at, then partner_configured_at. */
    /** Steps the partner does with an item that was not with them before. */
    public const PARTNER_STEPS = ['partner_received_at', 'partner_configured_at'];

    public function lastPartnerStep(): ?string
    {
        return $this->partner_configured_at !== null ? 'partner_configured_at'
            : ($this->partner_received_at !== null ? 'partner_received_at' : null);
    }

    public function nextPartnerStep(): ?string
    {
        if (! $this->needsPartnerSetup() || $this->assetReturn->warehouse_arrived_at === null) {
            return null;
        }

        return $this->partner_received_at === null ? 'partner_received_at'
            : ($this->partner_configured_at === null ? 'partner_configured_at' : null);
    }

    public function tag(): string
    {
        return (string) ($this->item?->asset_tag ?? '');
    }

    public function displayName(): string
    {
        $item = $this->item;
        if ($item instanceof Asset) {
            return (string) ($item->name ?: $item->model?->name);
        }

        return (string) ($item?->name ?? '');
    }
}
