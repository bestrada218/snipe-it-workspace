<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\License;
use App\Models\Setting;
use App\Services\AssetReplacement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;

/**
 * This controller handles all actions related to the Admin Dashboard
 * for the Snipe-IT Asset Management application.
 *
 * @author A. Gianotto <snipe@snipe.net>
 *
 * @version v1.0
 */
class DashboardController extends Controller
{
    /**
     * Check authorization and display the dashboard, otherwise display
     * the user's checked-out assets.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @since [v1.0]
     */
    public function index(): View|RedirectResponse
    {
        if (! Gate::allows('canViewUsersAndCheckoutables')) {
            Session::reflash();

            return Helper::safeIntended('account/view-assets');
        }

        // Top-boxes + empty-inventory shortcut are admin-only in the
        // dashboard blade, so the six count queries only need to run
        // for admins. Non-admins get an empty array and the view's
        // hasAccess('admin') gates short-circuit before touching any
        // $counts key.
        $counts = [];
        $fixedAssetMetric = null;
        if (auth()->user()->hasAccess('admin')) {
            $fixedModule = Setting::inventoryLocationModules()['fixed-assets'] ?? null;
            if ($fixedModule !== null && ($fixedModule['enabled'] ?? true) && Gate::allows('index', Asset::class)) {
                $fixedAssetMetric = [
                    'label' => Setting::inventoryLocationModuleLabel($fixedModule),
                    'count' => Asset::inInventoryLocationModule($fixedModule['location_id'])
                        ->join('status_labels', 'status_labels.id', '=', 'assets.status_id')
                        ->when(Setting::getSettings()->show_archived_in_list != 1, fn ($assets) => $assets->where('status_labels.archived', 0))
                        ->count(),
                ];
            }

            $counts['asset'] = Asset::count();
            // Accessory / Consumable / Component rows are parents with a
            // qty column that represents individual units in stock. Show
            // the sum of units across live non-archived rows instead of
            // the parent-row count so the dashboard reflects actual
            // inventory volume. SoftDeletes drops trashed parents, and
            // notArchived() excludes retired-but-live rows.
            $counts['accessory'] = (int) Accessory::notArchived()->sum('qty');
            $counts['license'] = License::assetcount();
            $counts['consumable'] = (int) Consumable::notArchived()->sum('qty');
            $counts['component'] = (int) Component::notArchived()->sum('qty');
            $counts['user'] = Company::scopeCompanyables(auth()->user())->count();
            // Components stay out of the grand total, as upstream: they are
            // parts installed inside assets. Disabled modules do not count.
            $grandTotalModules = ['assets' => 'asset', 'accessories' => 'accessory', 'licenses' => 'license', 'consumables' => 'consumable'];
            $counts['grand_total'] = 0;
            foreach ($grandTotalModules as $module => $countKey) {
                if (Setting::inventoryModuleEnabled($module)) {
                    $counts['grand_total'] += $counts[$countKey];
                }
            }
        }

        if ((! file_exists(storage_path().'/oauth-private.key')) || (! file_exists(storage_path().'/oauth-public.key'))) {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('passport:install', ['--no-interaction' => true]);
        }

        $replacementCounts = [];
        if (Gate::allows('index', Asset::class)) {
            foreach (AssetReplacement::windows() as $key => $window) {
                $replacementCounts[$key] = AssetReplacement::query($window)->count();
            }
        }

        return view('dashboard')
            ->with('replacementCounts', $replacementCounts)
            ->with('asset_stats', null)
            ->with('fixedAssetMetric', $fixedAssetMetric)
            ->with('counts', $counts);
    }
}
