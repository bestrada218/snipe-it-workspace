<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetShipment extends Model
{
    protected $fillable = [
        'recipient_id', 'created_by', 'platform', 'carrier', 'tracking_number',
        'approval_reference', 'approval_key', 'approval_filename', 'expected_on',
        'purchase_approver_id', 'purchase_approver_name',
    ];

    protected $casts = [
        'expected_on' => 'date',
        'received_at' => 'datetime',
        'assigned_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('assigned_at')->whereNull('cancelled_at');
    }

    public function isOpen(): bool
    {
        return ! $this->assigned_at && ! $this->cancelled_at;
    }

    public function status(): string
    {
        return match (true) {
            $this->cancelled_at !== null => 'cancelled',
            $this->assigned_at !== null => 'received',
            $this->received_at !== null => 'awaiting_assignment',
            $this->expected_on->isBefore(today()) => 'late',
            $this->expected_on->isSameDay(today()) => 'due',
            default => 'in_transit',
        };
    }
}
