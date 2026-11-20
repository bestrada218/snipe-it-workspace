<?php

namespace App\Models\Traits;

use App\Services\AssetTagSequence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Keeps the KKK number of the asset tag unique across every inventory module.
 *
 * The record and its reservation are written in one transaction: if another
 * record claims the same number between the availability check and the
 * write, the reservation fails and the record is rolled back with it, so a
 * caller never gets an error for a record that was in fact created. A new
 * record whose tag came from the tag builder then retries with the next
 * free number instead of failing.
 */
trait ReservesAssetTagSequence
{
    public static function bootReservesAssetTagSequence(): void
    {
        static::saving(fn ($model) => AssetTagSequence::assertAvailable($model));
    }

    /**
     * @param  Builder<static>  $query
     */
    protected function performInsert(Builder $query): bool
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($query): bool {
                    $inserted = parent::performInsert($query);
                    if ($inserted) {
                        AssetTagSequence::reserve($this);
                    }

                    return $inserted;
                });
            } catch (ValidationException $e) {
                // Lost the number to a save that committed in the meantime.
                // A builder-made tag retries with the next free number.
                if ($attempt >= 3 || ! AssetTagSequence::takeNextFree($this)) {
                    throw $e;
                }
                $this->exists = false;
                $this->wasRecentlyCreated = false;
                if ($this->getIncrementing()) {
                    $this->setAttribute($this->getKeyName(), null);
                }
            }
        }
    }

    /**
     * @param  Builder<static>  $query
     */
    protected function performUpdate(Builder $query): bool
    {
        return DB::transaction(function () use ($query): bool {
            $updated = parent::performUpdate($query);
            if ($updated && array_key_exists('asset_tag', $this->getChanges())) {
                AssetTagSequence::reserve($this);
            }

            return $updated;
        });
    }
}
