<?php

namespace App\Http\Controllers;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Location;
use App\Models\Setting;
use App\Services\AssetReturns;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use League\Csv\EscapeFormula;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WarehouseInventoryController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $types = [];
        if (Setting::inventoryModuleEnabled('assets') && Gate::allows('index', Asset::class)) {
            $types[] = 'asset';
        }
        if (Setting::inventoryModuleEnabled('accessories') && Gate::allows('index', Accessory::class)) {
            $types[] = 'accessory';
        }
        abort_if($types === [], 403);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in($types)],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'ownership' => ['nullable', Rule::in(['all', 'own', 'external'])],
            'state_id' => ['nullable', 'integer', 'min:1'],
            'export' => ['nullable', Rule::in(['csv'])],
        ]);

        $partnerId = AssetReturns::partnerLocation()?->id;
        $externalIds = Location::query()->where('is_external', true)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($partnerId !== null) {
            $externalIds[] = (int) $partnerId;
        }
        $ownership = $filters['ownership'] ?? 'all';
        if ($ownership === 'external') {
            unset($filters['state_id']);
        }
        if (isset($filters['location_id']) && $ownership !== 'all'
            && in_array((int) $filters['location_id'], $externalIds, true) !== ($ownership === 'external')) {
            unset($filters['location_id']);
        }

        $partnerLabel = trans('asset-returns.destinations.partner', ['partner' => AssetReturns::partnerName()]);
        $locations = Location::query()->select('locations.id');
        $locationTree = Location::query()->orderBy('name')->get(['locations.id', 'locations.name', 'locations.parent_id']);
        $children = $locationTree->groupBy('parent_id');
        $countryIds = $locationTree->whereNull('parent_id')->pluck('id');
        $states = $locationTree->whereInStrict('parent_id', $countryIds);
        $stateByLocation = [];
        foreach ($states as $state) {
            $pending = [$state->id];
            while ($pending !== []) {
                $id = array_pop($pending);
                if (isset($stateByLocation[$id])) {
                    continue;
                }
                $stateByLocation[$id] = (int) $state->id;
                foreach ($children->get($id, collect()) as $child) {
                    $pending[] = $child->id;
                }
            }
        }

        $stateIds = null;
        if (isset($filters['state_id'])) {
            $stateId = (int) $filters['state_id'];
            abort_unless($states->contains('id', $stateId), 404);
            $stateIds = array_keys($stateByLocation, $stateId, true);
            if (isset($filters['location_id']) && ($stateByLocation[$filters['location_id']] ?? null) !== $stateId) {
                unset($filters['location_id']);
            }
        }

        $inventory = $this->inventory(isset($filters['type']) ? [$filters['type']] : $types, $locations);
        $filtered = DB::query()->fromSub($inventory, 'inventory')
            ->join('locations', 'locations.id', '=', 'inventory.location_id')
            ->whereNull('locations.deleted_at')
            ->when($ownership === 'own', fn ($query) => $query->whereIntegerNotInRaw('inventory.location_id', $externalIds))
            ->when($ownership === 'external', fn ($query) => $query->whereIntegerInRaw('inventory.location_id', $externalIds))
            ->when($stateIds !== null, fn ($query) => $query->whereIntegerInRaw('inventory.location_id', array_values($stateIds)))
            ->when(isset($filters['location_id']), fn ($query) => $query->where('inventory.location_id', $filters['location_id']))
            ->when(filled($filters['q'] ?? null), function (Builder $query) use ($filters, $partnerId, $partnerLabel): void {
                $term = '%'.$filters['q'].'%';
                $query->where(fn (Builder $match) => $match->where('inventory.name', 'like', $term)
                    ->orWhere('inventory.tag', 'like', $term)
                    ->orWhere('inventory.serial', 'like', $term)
                    ->orWhere('locations.name', 'like', $term)
                    ->when($partnerId && mb_stripos($partnerLabel, $filters['q']) !== false,
                        fn (Builder $match) => $match->orWhere('locations.id', $partnerId)));
            });

        $rows = (clone $filtered)->select('inventory.*')
            ->selectRaw('CASE WHEN locations.id = ? THEN ? ELSE locations.name END AS warehouse', [$partnerId, $partnerLabel])
            ->orderBy('locations.name')->orderBy('inventory.tag')->orderBy('inventory.name')
            ->orderBy('inventory.type')->orderBy('inventory.item_id');

        if (($filters['export'] ?? null) === 'csv') {
            Gate::authorize('reports.view');

            return $this->export($rows);
        }

        $byWarehouse = (clone $filtered)->groupBy('locations.id', 'locations.name')
            ->selectRaw('locations.id, CASE WHEN locations.id = ? THEN ? ELSE locations.name END AS name, SUM(inventory.units) AS units', [$partnerId, $partnerLabel])
            ->orderByDesc('units')->orderBy('name')->get();
        $byType = (clone $filtered)->groupBy('inventory.type')->selectRaw('inventory.type, SUM(inventory.units) AS units')
            ->pluck('units', 'type');

        $warehouseOptions = Location::query()
            ->where(fn ($query) => $query->where('is_storage', true)->orWhereIn('locations.id',
                DB::query()->fromSub($this->inventory($types, $locations), 'options')->select('location_id')))
            ->orderBy('name')->pluck('name', 'id');

        if ($partnerId) {
            $warehouseOptions->put($partnerId, $partnerLabel);
        }

        return view('warehouse-inventory.index', [
            'types' => $types,
            'filters' => $filters,
            'warehouseOptions' => $warehouseOptions,
            'states' => $states,
            'ownership' => $ownership,
            'externalIds' => $externalIds,
            'stateByLocation' => $stateByLocation,
            'byWarehouse' => $byWarehouse,
            'byType' => $byType,
            'rows' => $rows->paginate(25)->withQueryString(),
        ]);
    }

    /** @param  list<string>  $types */
    private function inventory(array $types, \Illuminate\Database\Eloquent\Builder $locations): Builder
    {
        $queries = [];
        if (in_array('asset', $types, true)) {
            $queries[] = Asset::query()->notArchived()
                ->leftJoin('models', 'models.id', '=', 'assets.model_id')
                ->whereIn('assets.location_id', (clone $locations)->toBase())
                ->where(fn ($query) => $query->whereNull('assets.assigned_to')
                    ->orWhere(fn ($assigned) => $assigned->where('assets.assigned_type', Location::class)
                        ->whereColumn('assets.assigned_to', 'assets.location_id')))
                ->where(function ($query): void {
                    $query->whereIn('assets.location_id', Location::query()
                        ->where(fn ($location) => $location->where('is_storage', true)
                            ->orWhere('locations.id', Setting::getSettings()->return_partner_location_id))
                        ->select('locations.id'))
                        ->orWhereColumn('assets.warehouse_location_id', 'assets.location_id')
                        ->orWhereExists(function (Builder $returned): void {
                            $returned->selectRaw('1')->from('asset_return_items as returned')
                                ->where('returned.item_type', Asset::class)
                                ->whereColumn('returned.item_id', 'assets.id')
                                ->whereColumn('returned.location_id', 'assets.location_id')
                                ->whereNotNull('returned.stored_at')
                                ->where(fn (Builder $date) => $date->whereNull('assets.last_checkout')
                                    ->orWhereColumn('assets.last_checkout', '<=', 'returned.stored_at'));
                        });
                })
                ->selectRaw("assets.id AS item_id, assets.location_id, 'asset' AS type, assets.asset_tag AS tag,
                    COALESCE(NULLIF(assets.name, ''), models.name, assets.asset_tag) AS name, assets.serial, 1 AS units")
                ->toBase();
        }
        if (in_array('accessory', $types, true)) {
            $accessories = Accessory::query()->notArchived()->select(['accessories.id', 'accessories.name', 'accessories.asset_tag']);
            $queries[] = DB::table('accessory_stock_locations as stock')
                ->joinSub($accessories->toBase(), 'accessory', 'accessory.id', '=', 'stock.accessory_id')
                ->whereIn('stock.location_id', (clone $locations)->toBase())
                ->groupBy('accessory.id', 'stock.location_id', 'accessory.asset_tag', 'accessory.name')
                ->selectRaw("accessory.id AS item_id, stock.location_id, 'accessory' AS type, accessory.asset_tag AS tag,
                    accessory.name, NULL AS serial, COUNT(*) AS units");
        }

        $inventory = array_shift($queries);
        foreach ($queries as $query) {
            $inventory->unionAll($query);
        }

        return $inventory;
    }

    private function export(Builder $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            $formatter = new EscapeFormula;
            fputcsv($handle, [trans('warehouse-inventory.title'), trans('warehouse-inventory.type'),
                trans('general.asset_tag'), trans('general.item'), trans('general.serial_number'),
                trans('warehouse-inventory.units')], escape: '');

            foreach ($rows->cursor() as $row) {
                fputcsv($handle, $formatter->escapeRecord([$row->warehouse, trans('warehouse-inventory.'.$row->type),
                    $row->tag ?? '', $row->name, $row->serial ?? '', (string) $row->units]), escape: '');
            }

            fclose($handle);
        }, 'warehouse-inventory-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
