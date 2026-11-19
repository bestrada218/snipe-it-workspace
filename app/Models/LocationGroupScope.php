<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LocationGroupScope implements Scope
{
    private const LOCATION_MODELS = [Asset::class, User::class, Accessory::class, Consumable::class, Component::class];

    public static function currentUser(): ?User
    {
        return Auth::hasUser() ? Auth::user() : null;
    }

    /**
     * Null means unrestricted. An empty array means the selected trees no
     * longer have a common, accessible location.
     *
     * @return list<int>|null
     */
    public static function allowedIds(?User $actor): ?array
    {
        if (! $actor?->exists || $actor->isSuperUser()) {
            return null;
        }

        $key = self::class.':'.$actor->getKey();
        $attributes = request()->attributes;
        if ($attributes->has($key)) {
            return $attributes->get($key);
        }

        $roots = $actor->groups()->whereNotNull('permission_groups.scope_location_id')
            ->pluck('permission_groups.scope_location_id')->unique()->map(fn ($id) => (int) $id);

        $allowed = null;
        if ($roots->isNotEmpty()) {
            $locations = DB::table('locations')->whereNull('deleted_at')->get(['id', 'parent_id']);
            $byId = $locations->keyBy('id');
            $children = $locations->groupBy('parent_id');

            foreach ($roots as $root) {
                $tree = [];
                $pending = $byId->has($root) ? [$root] : [];
                while ($pending !== []) {
                    $id = array_pop($pending);
                    if (isset($tree[$id])) {
                        continue;
                    }
                    $tree[$id] = true;
                    foreach ($children->get($id, []) as $child) {
                        $pending[] = (int) $child->id;
                    }
                }
                $ids = array_keys($tree);
                $allowed = $allowed === null ? $ids : array_values(array_intersect($allowed, $ids));
            }
        }

        $attributes->set($key, $allowed);

        return $allowed;
    }

    public function apply(Builder $builder, Model $model): void
    {
        $ids = self::allowedIds(self::currentUser());
        if ($ids === null) {
            return;
        }

        if ($model instanceof Location) {
            $builder->whereIntegerInRaw($model->qualifyColumn('id'), $ids);
        } elseif ($model instanceof User) {
            // The signed-in person always resolves their own record, even when
            // their location sits outside the scope; otherwise they are signed out.
            $builder->where(fn (Builder $users) => $users
                ->whereIntegerInRaw($model->qualifyColumn('location_id'), $ids)
                ->orWhere($model->qualifyColumn('id'), self::currentUser()?->getKey()));
        } elseif (in_array($model::class, self::LOCATION_MODELS, true)) {
            $builder->whereIntegerInRaw($model->qualifyColumn('location_id'), $ids);
        } elseif ($model instanceof License) {
            // Licenses belong to a company, with no authoritative location.
            $builder->whereRaw('1 = 0');
        }
    }

    public static function allows(?User $actor, mixed $subject): bool
    {
        if (! $subject instanceof Model && ! is_string($subject)) {
            return true;
        }

        $ids = self::allowedIds($actor);
        if ($ids === null) {
            return true;
        }

        $type = is_object($subject) ? $subject::class : $subject;
        if (in_array($type, [License::class, LicenseSeat::class, Maintenance::class, PredefinedKit::class], true)) {
            return false;
        }

        if (! $subject instanceof Model || ! $subject->exists) {
            return true;
        }

        if ($subject instanceof Location) {
            return in_array((int) $subject->getKey(), $ids, true);
        }

        if ($subject instanceof User && $subject->is($actor)) {
            return true;
        }

        return ! in_array($type, self::LOCATION_MODELS, true)
            || in_array((int) $subject->location_id, $ids, true);
    }

    public static function validateSave(Model $model): void
    {
        $ids = self::allowedIds(self::currentUser());
        if ($ids === null) {
            return;
        }

        if ($model instanceof Location || $model instanceof License) {
            abort(403);
        }

        if (! in_array($model::class, self::LOCATION_MODELS, true)) {
            return;
        }

        // Signing in and editing one's own profile save the actor's record;
        // only a change of location is still checked against the scope.
        if ($model instanceof User && $model->is(self::currentUser()) && ! $model->isDirty('location_id')) {
            return;
        }

        if ($model->exists && ! in_array((int) $model->getRawOriginal('location_id'), $ids, true)) {
            abort(403);
        }

        if (! in_array((int) $model->location_id, $ids, true)) {
            throw ValidationException::withMessages(['location_id' => trans('admin/groups/titles.location_scope_violation')]);
        }

        if ($model instanceof Asset && $model->isDirty('rtd_location_id')
            && $model->rtd_location_id !== null && ! in_array((int) $model->rtd_location_id, $ids, true)) {
            throw ValidationException::withMessages(['rtd_location_id' => trans('admin/groups/titles.location_scope_violation')]);
        }

        if ($model instanceof Asset && $model->isDirty(['assigned_type', 'assigned_to']) && $model->assigned_to) {
            $target = $model->assigned_type;
            $targetColumn = $target === Location::class ? 'id' : 'location_id';
            $targetLocation = match ($target) {
                Location::class, User::class, Asset::class => $target::withoutGlobalScope(self::class)
                    ->whereKey($model->assigned_to)->value($targetColumn),
                default => null,
            };
            if (! in_array((int) $targetLocation, $ids, true)) {
                throw ValidationException::withMessages(['assigned_to' => trans('admin/groups/titles.location_scope_violation')]);
            }
        }
    }
}
