<?php

namespace App\Services;

use App\Enums\FileStorage;
use App\Helpers\Helper;
use App\Models\Accessory;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class DuplicateAssignments
{
    public const TYPES = [
        'assets' => Asset::class,
        'accessories' => Accessory::class,
        'consumables' => Consumable::class,
        'licenses' => License::class,
    ];

    public static function approvers(): Collection
    {
        return User::whereKey(Setting::getSettings()?->duplicate_assignment_approvers ?? [])
            ->orderBy('first_name')->orderBy('last_name')->get();
    }

    public static function canViewAlerts(): bool
    {
        return Gate::allows('view', User::class)
            && collect(self::TYPES)->contains(fn (string $model) => Gate::allows('index', $model));
    }

    public static function alerts(): Builder
    {
        return DB::query()->fromSub(self::visibleAssignments(), 'assignments')
            ->whereIn('person_id', User::query()->select('users.id'))
            ->join('categories', 'categories.id', '=', 'assignments.category_id')
            ->whereNull('categories.deleted_at')
            ->select('person_id', 'category_id', 'categories.name as category')
            ->selectRaw('COUNT(*) as units')
            ->groupBy('person_id', 'category_id', 'categories.name')
            ->havingRaw('COUNT(*) > 1');
    }

    public static function members(array $people, array $categories): \Illuminate\Support\Collection
    {
        return DB::query()->fromSub(self::visibleAssignments(), 'assignments')
            ->whereIntegerInRaw('person_id', $people)
            ->whereIntegerInRaw('category_id', $categories)
            ->select('person_id', 'category_id', 'item_type', 'item_id')
            ->selectRaw('COUNT(*) as units, MAX(assigned_at) as assigned_at')
            ->groupBy('person_id', 'category_id', 'item_type', 'item_id')
            ->orderBy('assigned_at')
            ->orderBy('item_id')
            ->get();
    }

    private static function visibleAssignments(): Builder
    {
        $queries = collect(self::TYPES)
            ->filter(fn (string $model) => Gate::allows('index', $model))
            ->map(fn (string $model) => self::assignments($model))
            ->values();
        $assignments = $queries->shift();
        foreach ($queries as $query) {
            $assignments->unionAll($query);
        }

        return $assignments;
    }

    /**
     * @return array{category: ?string, existing: int, incoming: int, duplicate: bool}
     */
    public static function inspect(Asset|Accessory|Consumable|License $item, User $person, int $incoming, ?int $ignoreSeat = null): array
    {
        $category = $item instanceof Asset ? $item->model?->category : $item->category;
        $existing = $category ? self::assignments($item::class, $ignoreSeat, $person->id, $category->id)->count() : 0;

        return [
            'category' => $category?->name,
            'existing' => $existing,
            'incoming' => $incoming,
            'duplicate' => $category !== null && $existing + $incoming > 1,
        ];
    }

    /**
     * Call after locking the recipient, inside the checkout transaction.
     */
    public static function hasCurrentAssetAssignment(Asset $asset, User $person): bool
    {
        $categoryId = $asset->model?->category?->id;

        return $categoryId !== null
            && self::assignments(Asset::class, null, $person->id, $categoryId)->lockForUpdate()->first() !== null;
    }

    /**
     * Call within the same transaction as the assignment write.
     */
    public static function confirm(Request $request, Asset|Accessory|Consumable|License $item, mixed $target, int $incoming = 1, ?int $ignoreSeat = null): void
    {
        if (! $target instanceof User) {
            return;
        }

        $person = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
        $category = $item instanceof Asset ? $item->model?->category : $item->category;
        if (! $category) {
            return;
        }

        // A locking read sees a checkout that committed while this request was waiting.
        if ($incoming === 1 && ! self::assignments($item::class, $ignoreSeat, $person->id, $category->id)->lockForUpdate()->first()) {
            return;
        }

        $reason = $request->input('duplicate_assignment_reason');
        $reason = is_string($reason) ? trim($reason) : '';
        if (! $request->boolean('duplicate_assignment_confirmed') || $reason === '' || mb_strlen($reason) > 1000) {
            throw ValidationException::withMessages([
                'duplicate_assignment_reason' => trans('duplicate-assignments.confirm_required', ['category' => $category->name]),
            ]);
        }

        $approvers = self::approvers()->keyBy('id');
        if ($approvers->isEmpty()) {
            throw ValidationException::withMessages([
                'duplicate_assignment_approver' => trans('duplicate-assignments.no_approvers'),
            ]);
        }

        $validated = $request->validate([
            'duplicate_assignment_approver' => ['required', 'integer', Rule::in($approvers->keys()->all())],
            'duplicate_assignment_evidence' => ['bail', 'required', 'file', 'extensions:pdf,jpg,jpeg,png,webp', 'mimes:pdf,jpg,jpeg,png,webp', 'max:'.(int) ceil(Helper::file_upload_max_size() / 1024)],
        ], [], [
            'duplicate_assignment_approver' => trans('duplicate-assignments.approved_by'),
            'duplicate_assignment_evidence' => trans('duplicate-assignments.evidence'),
        ]);

        $approver = $approvers->get($validated['duplicate_assignment_approver']);
        request()->attributes->set(self::approvalKey($item::class, $item->id, $target->id), [
            'reason' => $reason,
            'approver_id' => $approver->id,
            'approver_name' => $approver->display_name,
            'category_id' => $category->id,
            'file' => $validated['duplicate_assignment_evidence'],
        ]);
    }

    public static function recordApproval(mixed $item, mixed $target): array
    {
        if (! $target instanceof User) {
            return [];
        }

        $type = $item instanceof LicenseSeat ? License::class : $item::class;
        $id = $item instanceof LicenseSeat ? $item->license_id : $item->getKey();
        if (! $id || ! in_array($type, self::TYPES, true)) {
            return [];
        }

        $attributes = request()->attributes;
        $approvalKey = self::approvalKey($type, (int) $id, $target->id);
        $approval = $attributes->get($approvalKey);
        if ($approval === null) {
            return [];
        }

        $attributes->remove($approvalKey);
        $file = $approval['file'];
        $key = Str::uuid().'.'.$file->guessExtension();
        $folder = FileStorage::DuplicateApprovals->privateStorageKey();
        $path = $folder.$key;
        $committed = false;
        app()->terminating(function () use (&$committed, $path): void {
            if (! $committed) {
                Storage::delete($path);
            }
        });

        if (! Storage::putFileAs($folder, $file, $key)) {
            throw ValidationException::withMessages([
                'duplicate_assignment_evidence' => trans('duplicate-assignments.evidence_store_failed'),
            ]);
        }

        DB::afterCommit(function () use (&$committed): void {
            $committed = true;
        });

        $filename = preg_replace('/[\x00-\x1f\x7f]/u', '', $file->getClientOriginalName()) ?: $key;

        return [
            '_duplicate_assignment_reason' => $approval['reason'],
            '_duplicate_assignment_approval' => [
                'approver_id' => $approval['approver_id'],
                'approver_name' => $approval['approver_name'],
                'category_id' => $approval['category_id'],
                'filename' => mb_substr($filename, 0, 255),
                'key' => $key,
            ],
        ];
    }

    public static function canViewApproval(Actionlog $log): bool
    {
        return $log->item && Gate::allows('view', $log->item) && Gate::allows('history', $log->item);
    }

    public static function evidencePath(array $approval): ?string
    {
        if (! is_string($approval['key'] ?? null)
            || ! preg_match('/\A[0-9a-f-]{36}\.(pdf|jpe?g|png|webp)\z/', $approval['key'])) {
            return null;
        }

        return FileStorage::DuplicateApprovals->privateStorageKey().$approval['key'];
    }

    public static function exportMetadata(Actionlog $log): ?string
    {
        if (! str_contains($log->log_meta ?? '', '"_duplicate_assignment_')) {
            return $log->log_meta;
        }

        $meta = json_decode($log->log_meta, true);
        if (! is_array($meta)) {
            return $log->log_meta;
        }

        if (! self::canViewApproval($log)) {
            unset($meta['_duplicate_assignment_reason'], $meta['_duplicate_assignment_approval']);
        } else {
            unset($meta['_duplicate_assignment_approval']['key']);
        }

        return json_encode($meta);
    }

    private static function approvalKey(string $type, int $item, int $person): string
    {
        return self::class.':'.$type.':'.$item.':'.$person;
    }

    private static function assignments(string $model, ?int $ignoreSeat = null, ?int $person = null, ?int $category = null): Builder
    {
        [$query, $personColumn, $categoryColumn, $itemColumn, $assignedColumn] = match ($model) {
            Asset::class => [
                Asset::query()->notArchived()
                    ->join('models', 'models.id', '=', 'assets.model_id')
                    ->where('assets.archived', 0)
                    ->where('assets.assigned_type', User::class),
                'assets.assigned_to',
                'models.category_id',
                'assets.id',
                'assets.last_checkout',
            ],
            Accessory::class => [
                Accessory::query()->notArchived()
                    ->join('accessories_checkout', 'accessories_checkout.accessory_id', '=', 'accessories.id')
                    ->where('accessories_checkout.assigned_type', User::class),
                'accessories_checkout.assigned_to',
                'accessories.category_id',
                'accessories.id',
                'accessories_checkout.created_at',
            ],
            Consumable::class => [
                Consumable::query()->notArchived()
                    ->join('consumables_users', 'consumables_users.consumable_id', '=', 'consumables.id')
                    ->whereNull('consumables_users.consumed_at'),
                'consumables_users.assigned_to',
                'consumables.category_id',
                'consumables.id',
                'consumables_users.created_at',
            ],
            License::class => [
                License::query()
                    ->join('license_seats', 'license_seats.license_id', '=', 'licenses.id')
                    ->whereNull('license_seats.deleted_at')
                    ->whereNull('license_seats.asset_id')
                    ->when($ignoreSeat !== null, fn ($query) => $query->where('license_seats.id', '!=', $ignoreSeat)),
                'license_seats.assigned_to',
                'licenses.category_id',
                'licenses.id',
                'license_seats.updated_at',
            ],
        };

        return $query->whereNotNull($personColumn)
            ->when($person !== null, fn ($query) => $query->where($personColumn, $person))
            ->when($category !== null, fn ($query) => $query->where($categoryColumn, $category))
            ->select($personColumn.' as person_id', $categoryColumn.' as category_id')
            ->addSelect($itemColumn.' as item_id', $assignedColumn.' as assigned_at')
            ->selectRaw('? as item_type', [$model])
            ->toBase();
    }
}
