<?php

namespace App\Models\Traits;

use App\Services\AssetTagSequence;

/**
 * Keeps the KKK number of the asset tag unique across every inventory module.
 */
trait ReservesAssetTagSequence
{
    public static function bootReservesAssetTagSequence(): void
    {
        static::saving(fn ($model) => AssetTagSequence::assertAvailable($model));
        static::saved(fn ($model) => AssetTagSequence::reserve($model));
    }
}
