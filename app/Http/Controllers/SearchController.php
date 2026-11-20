<?php

namespace App\Http\Controllers;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\AssetModel;
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

    /** Rows shown per section, and when the person asks for more. */
    private const LIMITS = ['assets' => 25, 'items' => 10, 'people' => 15];

    private const EXPANDED_LIMIT = 200;

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
        abort_unless(self::isAvailable(), 403);

        $term = trim((string) $request->query('q', ''));
        $more = (string) $request->query('more', '');
        $assets = null;
        $items = [];
        $people = null;

        if (mb_strlen($term) >= self::MIN_LENGTH) {
            $like = '%'.addcslashes($term, '%_\\').'%';

            if (self::canSearchAssets()) {
                $exact = Asset::where('asset_tag', $term)->first();
                if ($exact && ! $request->boolean('all')) {
                    return redirect()->route('hardware.show', $exact);
                }

                // Assets without a name are shown by their model, so the model
                // name is searched too.
                $assets = $this->limited(Asset::with(['model', 'status', 'assignedTo'])
                    ->where(fn (Builder $query) => $query->where('assets.asset_tag', 'like', $like)
                        ->orWhere('assets.serial', 'like', $like)
                        ->orWhere('assets.name', 'like', $like)
                        ->orWhereIn('assets.model_id', AssetModel::where('name', 'like', $like)->select('id')))
                    ->orderBy('assets.asset_tag'), $more === 'assets' ? self::EXPANDED_LIMIT : self::LIMITS['assets']);
            }

            foreach (self::TAGGED_ITEMS as $module => [$class, $ability, $route]) {
                if (! self::canSearchModule($module)) {
                    continue;
                }

                $table = (new $class)->getTable();
                $found = $this->limited($class::query()
                    ->where(fn (Builder $query) => $query->where($table.'.asset_tag', 'like', $like)
                        ->orWhere($table.'.name', 'like', $like))
                    ->orderBy($table.'.asset_tag'), $more === $module ? self::EXPANDED_LIMIT : self::LIMITS['items']);
                if ($found['rows']->isNotEmpty()) {
                    $items[$module] = $found + ['route' => $route];
                }
            }

            if (Gate::allows('view', User::class)) {
                $people = $this->limited(User::with('location')
                    ->where(fn (Builder $query) => $query->where('users.username', 'like', $like)
                        ->orWhere('users.email', 'like', $like)
                        ->orWhere('users.employee_num', 'like', $like)
                        ->orWhereRaw("CONCAT(users.first_name, ' ', COALESCE(users.last_name, '')) LIKE ?", [$like]))
                    ->orderBy('users.first_name'), $more === 'people' ? self::EXPANDED_LIMIT : self::LIMITS['people']);
            }
        }

        return view('search.index', [
            'term' => $term,
            'tooShort' => $term !== '' && mb_strlen($term) < self::MIN_LENGTH,
            'assets' => $assets,
            'items' => $items,
            'people' => $people,
            'nothingFound' => mb_strlen($term) >= self::MIN_LENGTH
                && ($assets['rows'] ?? collect())->isEmpty() && $items === [] && ($people['rows'] ?? collect())->isEmpty(),
        ]);
    }

    /**
     * Whether the header search applies to this person: any inventory module
     * they can open, or people.
     */
    public static function isAvailable(): bool
    {
        return self::canSearchAssets()
            || Gate::allows('view', User::class)
            || collect(array_keys(self::TAGGED_ITEMS))->contains(fn (string $module) => self::canSearchModule($module));
    }

    /**
     * Fetch one row past the limit to know whether there is more to show.
     *
     * @return array{rows: Collection, more: bool}
     */
    private function limited(Builder $query, int $limit): array
    {
        $rows = $query->limit($limit + 1)->get();

        return ['rows' => $rows->take($limit), 'more' => $rows->count() > $limit && $limit < self::EXPANDED_LIMIT];
    }

    private static function canSearchModule(string $module): bool
    {
        [$class, $ability] = self::TAGGED_ITEMS[$module];

        return Setting::inventoryModuleEnabled($module) && Gate::allows($ability, $class);
    }

    private static function canSearchAssets(): bool
    {
        return Setting::inventoryModuleEnabled('assets') && Gate::allows('index', Asset::class);
    }
}
