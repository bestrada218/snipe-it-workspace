<?php

namespace App\Services;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\License;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The KKK part of an asset tag (XX-KKK-PP[-TTT]) identifies one piece across
 * the whole inventory. Each number in use is reserved in asset_tag_sequences,
 * whose unique index rejects a second record with the same number.
 */
final class AssetTagSequence
{
    /**
     * @var array<class-string<Model>, string>
     */
    public const MODULE_TABLES = [
        Asset::class => 'assets',
        Accessory::class => 'accessories',
        Consumable::class => 'consumables',
        Component::class => 'components',
        License::class => 'licenses',
    ];

    /** Common TTT suffixes from the tag guide. */
    public const SUFFIXES = ['W11', 'MAC', 'LNX'];

    private const PATTERN = '/^[A-Z0-9]{2}-(\d{3})-[A-Z0-9]{2}(?:-[A-Z0-9]{3})?$/iD';

    public static function extract(?string $tag): ?int
    {
        return $tag !== null && preg_match(self::PATTERN, trim($tag), $parts) ? (int) $parts[1] : null;
    }

    public static function next(): ?string
    {
        // Deleted and archived records keep their number, so it is never reused.
        $highest = (int) DB::table('asset_tag_sequences')->max('sequence');

        return $highest < 999 ? sprintf('%03d', $highest + 1) : null;
    }

    /**
     * XX location prefixes already used in tags, for the tag builder.
     *
     * @return list<string>
     */
    public static function knownPrefixes(): array
    {
        $prefixes = collect(['NY', 'NJ']);
        foreach (self::MODULE_TABLES as $table) {
            $prefixes = $prefixes->merge(DB::table($table)->where('asset_tag', 'like', '__-___-__%')
                ->selectRaw('DISTINCT UPPER(LEFT(asset_tag, 2)) AS prefix')->pluck('prefix'));
        }

        return $prefixes->filter(fn ($prefix) => preg_match('/^[A-Z0-9]{2}$/', (string) $prefix))->unique()->sort()->values()->all();
    }

    public static function hint(): string
    {
        $next = self::next();

        return $next === null
            ? trans('asset-tags.sequence_exhausted')
            : trans('asset-tags.next_sequence', ['sequence' => $next]);
    }

    /**
     * Saving hook: stop before writing when another record holds the number.
     */
    public static function assertAvailable(Model $model): void
    {
        $sequence = self::extract($model->asset_tag);
        if ($sequence === null) {
            return;
        }

        // Tags from the builder: a known type (Settings > Asset Tags > Tag
        // types), with a suffix only on the types that allow one.
        if (self::assignsFreeNumber() && $model->isDirty('asset_tag')) {
            $parts = explode('-', strtoupper(trim($model->asset_tag)));
            if (! in_array($parts[2], AssetTagTypes::codes(), true)) {
                throw ValidationException::withMessages([
                    'asset_tag' => trans('asset-tag-types.unknown_code', ['code' => $parts[2]]),
                ]);
            }
            if (isset($parts[3]) && ! in_array($parts[2], AssetTagTypes::suffixCodes(), true)) {
                throw ValidationException::withMessages([
                    'asset_tag' => trans('asset-tags.suffix_only_computers', ['codes' => implode(', ', AssetTagTypes::suffixCodes())]),
                ]);
            }
        }

        $holder = DB::table('asset_tag_sequences')->where('sequence', $sequence)->first();
        if ($holder && ! self::isHeldBy($holder, $model)) {
            // A tag built with the tag builder only previews the next number;
            // if someone saved first, the new record takes the next free one.
            if (! $model->exists && self::takeNextFree($model)) {
                return;
            }
            self::taken($sequence, $holder);
        }
    }

    /**
     * Whether this request comes from a form whose tag was built with the tag
     * builder. Only then may a new record's number be changed on save; API
     * calls and imports keep their tag and get an error instead.
     */
    public static function assignsFreeNumber(): bool
    {
        return request()->boolean('asset_tag_from_builder');
    }

    /**
     * Gives a new builder-made tag the next free number, keeping XX, PP and
     * TTT. Returns false when that is not allowed or no number is left.
     */
    public static function takeNextFree(Model $model): bool
    {
        $previous = self::extract($model->asset_tag);
        $next = self::next();
        if ($previous === null || $next === null || ! self::assignsFreeNumber()) {
            return false;
        }

        $model->asset_tag = self::withSequence($model->asset_tag, (int) $next);
        session()->flash('warning', trans('asset-tags.sequence_reassigned', [
            'previous' => sprintf('%03d', $previous),
            'tag' => $model->asset_tag,
        ]));

        return true;
    }

    public static function withSequence(string $tag, int $sequence): string
    {
        $parts = explode('-', strtoupper(trim($tag)));
        $parts[1] = sprintf('%03d', $sequence);

        return implode('-', $parts);
    }

    /**
     * Saved hook: record (or move) this record's reservation.
     */
    public static function reserve(Model $model): void
    {
        $sequence = self::extract($model->asset_tag);
        $key = ['item_type' => $model::class, 'item_id' => $model->getKey()];

        if ($sequence === null) {
            DB::table('asset_tag_sequences')->where($key)->delete();

            return;
        }

        try {
            DB::table('asset_tag_sequences')->updateOrInsert($key, ['sequence' => $sequence, 'updated_at' => now(), 'created_at' => now()]);
        } catch (UniqueConstraintViolationException) {
            // Someone else claimed the number between the check and the write.
            self::taken($sequence, DB::table('asset_tag_sequences')->where('sequence', $sequence)->first());
        }
    }

    /**
     * Numbers used by more than one record, read from the module tables
     * themselves (deleted records included).
     *
     * @return Collection<int, Collection<int, array{type: string, id: int, tag: string, deleted: bool}>>
     */
    public static function duplicates(): Collection
    {
        $all = collect();
        foreach (self::MODULE_TABLES as $class => $table) {
            DB::table($table)->whereNotNull('asset_tag')->orderBy('id')->get(['id', 'asset_tag', 'deleted_at'])
                ->each(function ($row) use ($all, $class) {
                    $sequence = self::extract($row->asset_tag);
                    if ($sequence !== null) {
                        $all->push(['sequence' => $sequence, 'type' => class_basename($class), 'id' => (int) $row->id, 'tag' => $row->asset_tag, 'deleted' => $row->deleted_at !== null]);
                    }
                });
        }

        return $all->groupBy('sequence')->filter(fn (Collection $rows) => $rows->count() > 1)->sortKeys();
    }

    private static function isHeldBy(object $holder, Model $model): bool
    {
        return $model->exists && $holder->item_type === $model::class && (int) $holder->item_id === (int) $model->getKey();
    }

    private static function taken(int $sequence, ?object $holder): never
    {
        $tag = null;
        if ($holder && isset(self::MODULE_TABLES[$holder->item_type])) {
            $tag = DB::table(self::MODULE_TABLES[$holder->item_type])->where('id', $holder->item_id)->value('asset_tag');
        }

        throw ValidationException::withMessages([
            'asset_tag' => trans('asset-tags.sequence_taken', [
                'sequence' => sprintf('%03d', $sequence),
                'tag' => $tag ?? sprintf('%03d', $sequence),
            ]),
        ]);
    }
}
