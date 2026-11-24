<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A person returning what they hold, because they leave (termination) or
 * because items are being exchanged (change).
 */
class AssetReturn extends Model
{
    use HasFactory;

    public const REASONS = ['termination', 'change'];

    /** Box shipment to the partner warehouse, in order. */
    public const SHIPPING_STEPS = ['address_confirmed_at', 'boxes_sent_at', 'boxes_shipped_at', 'warehouse_arrived_at'];

    protected $fillable = ['user_id', 'reason', 'ticket_number', 'ticket_owner_id', 'return_owner_id', 'carrier', 'tracking_number', 'created_by'];

    protected $casts = [
        'address_confirmed_at' => 'datetime',
        'boxes_sent_at' => 'datetime',
        'boxes_shipped_at' => 'datetime',
        'warehouse_arrived_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function ticketOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ticket_owner_id')->withTrashed();
    }

    public function returnOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'return_owner_id')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssetReturnItem::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(AssetReturnNote::class)->latest('id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('completed_at')->whereNull('cancelled_at');
    }

    public function isOpen(): bool
    {
        return $this->completed_at === null && $this->cancelled_at === null;
    }

    public function hasPartnerItems(): bool
    {
        return $this->items->contains(fn (AssetReturnItem $item) => $item->goesToPartner());
    }

    /** The next box-shipment step still to do, or null when all are done. */
    /** The last shipping step marked, which can still be undone (not the arrival: it checks items in). */
    public function lastShippingStep(): ?string
    {
        $last = null;
        foreach (self::SHIPPING_STEPS as $step) {
            if ($this->{$step} !== null) {
                $last = $step;
            }
        }

        return $last;
    }

    public function nextShippingStep(): ?string
    {
        foreach (self::SHIPPING_STEPS as $step) {
            if ($this->{$step} === null) {
                return $step;
            }
        }

        return null;
    }

    /** Nothing has been checked in yet, so it can still be cancelled. */
    public function canBeCancelled(): bool
    {
        return $this->isOpen()
            && $this->warehouse_arrived_at === null
            && ! $this->items->contains(fn (AssetReturnItem $item) => $item->stored_at !== null);
    }

    public function status(): string
    {
        return match (true) {
            $this->cancelled_at !== null => 'cancelled',
            $this->completed_at !== null => 'completed',
            default => 'open',
        };
    }
}
