<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Livewire\NeedsAttention;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\License;
use App\Models\Setting;
use App\Services\AssetReplacement;
use App\Services\AssetShipments;
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
                    'count' => Asset::inInventoryLocationModule($fixedModule['location_id'], $fixedModule['category_ids'] ?? [])
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
            ->with('attention', $this->attentionItems($replacementCounts))
            ->with('replacementCounts', $replacementCounts)
            ->with('asset_stats', null)
            ->with('fixedAssetMetric', $fixedAssetMetric)
            ->with('counts', $counts);
    }

    /**
     * What needs someone's action today, most urgent first, in one list.
     * Only items the person may act on, and only when there is something
     * to do. The non-asset reminders reuse the NeedsAttention counts so
     * both stay in step.
     *
     * @param  array<string, int>  $replacementCounts
     * @return list<array{tone: string, title: string, detail: string, url: string, action: string}>
     */
    private function attentionItems(array $replacementCounts): array
    {
        $items = [];
        $user = auth()->user();
        $reminders = new NeedsAttention;
        $reminders->mount();

        if (Setting::inventoryModuleEnabled('assets') && Gate::allows('index', Asset::class)) {
            $overdueReplacement = $replacementCounts['overdue'] ?? 0;
            if ($overdueReplacement > 0) {
                $items[] = $this->attentionItem('crit', 'replacement_overdue', $overdueReplacement,
                    route('hardware.replacement-alerts', ['window' => 'overdue']), 'review');
            }

            $dueShipments = AssetShipments::dueFor($user)->count();
            if ($dueShipments > 0) {
                $items[] = $this->attentionItem('warn', 'shipments_due', $dueShipments,
                    route('shipments.index', ['due' => 1]), 'confirm_delivery');
            }

            $soonReplacement = ($replacementCounts['one_day'] ?? 0) + ($replacementCounts['fifteen_days'] ?? 0);
            if ($soonReplacement > 0) {
                $items[] = $this->attentionItem('warn', 'replacement_soon', $soonReplacement,
                    route('hardware.replacement-alerts', ['window' => 'fifteen_days']), 'plan');
            }

            if (Gate::allows('checkin', Asset::class)) {
                $overdueCheckins = Asset::OverdueForCheckin()->count();
                if ($overdueCheckins > 0) {
                    $items[] = $this->attentionItem('warn', 'checkins_overdue', $overdueCheckins,
                        route('assets.checkins.due'), 'review');
                }
            }

            if (Gate::allows('audit', Asset::class)) {
                $overdueAudits = Asset::OverdueForAudit()->count();
                if ($overdueAudits > 0) {
                    $items[] = $this->attentionItem('info', 'audits_overdue', $overdueAudits,
                        route('assets.audit.due'), 'audit');
                }
            }

            if ($reminders->assetsPastEol > 0) {
                $items[] = $this->attentionItem('info', 'past_eol', $reminders->assetsPastEol,
                    route('hardware.index', ['past_eol' => 1]), 'review');
            }
        }

        if ($reminders->overdueMaintenances > 0) {
            $items[] = $this->attentionItem('warn', 'maintenances_overdue', $reminders->overdueMaintenances,
                route('maintenances.index', ['upcoming_status' => 'overdue']), 'review');
        }

        if ($reminders->usersOffboardingSoon > 0) {
            $items[] = $this->attentionItem('warn', 'offboarding', $reminders->usersOffboardingSoon,
                route('users.index', ['end_date_within' => NeedsAttention::OFFBOARDING_DAYS, 'with_assignments' => 1]), 'review');
        }

        if ($reminders->licensesExpiringSoon > 0) {
            $items[] = $this->attentionItem('info', 'licenses_expiring', $reminders->licensesExpiringSoon,
                route('licenses.index', ['status' => 'expiring']), 'review');
        }

        if ($reminders->pendingRequestsCount > 0) {
            $items[] = $this->attentionItem('info', 'requests_pending', $reminders->pendingRequestsCount,
                route('requests.index'), 'review');
        }

        if ($reminders->pendingAcceptancesCount > 0) {
            $items[] = $this->attentionItem('info', 'acceptances_pending', $reminders->pendingAcceptancesCount,
                route('unaccepted-acceptances.index'), 'review');
        }

        return $items;
    }

    /**
     * @return array{tone: string, title: string, detail: string, url: string, action: string}
     */
    private function attentionItem(string $tone, string $key, int $count, string $url, string $action): array
    {
        return [
            'tone' => $tone,
            'title' => trans_choice('dashboard-attention.'.$key, $count),
            'detail' => trans('dashboard-attention.'.$key.'_detail'),
            'url' => $url,
            'action' => trans('dashboard-attention.'.$action),
        ];
    }
}
