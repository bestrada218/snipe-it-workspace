<?php

namespace App\Http\Controllers;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Global search from the header: one box for asset tags, serial numbers,
 * item names and people. Every query goes through the models' global scopes,
 * so company and location scoping still apply.
 */
class SearchController extends Controller
{
    private const MIN_LENGTH = 2;

    /**
     * Tagged inventory modules searched besides assets, with the ability the
     * side menu uses to show each one.
     *
     * @var array<string, array{0: class-string, 1: string, 2: string}>
     */
    private const TAGGED_ITEMS = [
        'accessories' => [Accessory::class, 'index', 'accessories.show'],
        'consumables' => [Consumable::class, 'view', 'consumables.show'],
        'components' => [Component::class, 'view', 'components.show'],
        'licenses' => [License::class, 'view', 'licenses.show'],
    ];

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->canSearchAssets() || Gate::allows('view', User::class), 403);

        $term = trim((string) $request->query('q', ''));
        $assets = collect();
        $items = collect();
        $people = collect();

        if (mb_strlen($term) >= self::MIN_LENGTH) {
            $like = '%'.addcslashes($term, '%_\\').'%';

            if ($this->canSearchAssets()) {
                $exact = Asset::where('asset_tag', $term)->first();
                if ($exact && ! $request->boolean('all')) {
                    return redirect()->route('hardware.show', $exact);
                }

                $assets = Asset::with(['model', 'status', 'assignedTo'])
                    ->where(fn (Builder $query) => $query->where('assets.asset_tag', 'like', $like)
                        ->orWhere('assets.serial', 'like', $like)
                        ->orWhere('assets.name', 'like', $like))
                    ->orderBy('assets.asset_tag')
                    ->limit(25)
                    ->get();
            }

            $items = $this->taggedItems($like);

            if (Gate::allows('view', User::class)) {
                $people = User::with('location')
                    ->where(fn (Builder $query) => $query->where('users.username', 'like', $like)
                        ->orWhere('users.email', 'like', $like)
                        ->orWhere('users.employee_num', 'like', $like)
                        ->orWhereRaw("CONCAT(users.first_name, ' ', COALESCE(users.last_name, '')) LIKE ?", [$like]))
                    ->orderBy('users.first_name')
                    ->limit(15)
                    ->get();
            }
        }

        return view('search.index', [
            'term' => $term,
            'tooShort' => $term !== '' && mb_strlen($term) < self::MIN_LENGTH,
            'assets' => $assets,
            'items' => $items,
            'people' => $people,
        ]);
    }

    private function canSearchAssets(): bool
    {
        return Setting::inventoryModuleEnabled('assets') && Gate::allows('index', Asset::class);
    }

    /**
     * @return Collection<int, array{module: string, model: \Illuminate\Database\Eloquent\Model, route: string}>
     */
    private function taggedItems(string $like): Collection
    {
        $found = collect();
        foreach (self::TAGGED_ITEMS as $module => [$class, $ability, $route]) {
            if (! Setting::inventoryModuleEnabled($module) || Gate::denies($ability, $class)) {
                continue;
            }

            $table = (new $class)->getTable();
            $class::query()
                ->where(fn (Builder $query) => $query->where($table.'.asset_tag', 'like', $like)
                    ->orWhere($table.'.name', 'like', $like))
                ->orderBy($table.'.asset_tag')
                ->limit(10)
                ->get()
                ->each(fn ($model) => $found->push(['module' => $module, 'model' => $model, 'route' => $route]));
        }

        return $found;
    }
}
