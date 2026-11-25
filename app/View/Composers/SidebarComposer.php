<?php

// A View Composer is a callback Laravel runs right before a specific view renders.
// It's registered in AppServiceProvider bound to 'layouts.default', so it only fires
// when a full page is rendered — not on modal AJAX responses, select2 searches, or
// API requests. This replaces the old AssetCountForSidebar middleware, which ran on
// every web request regardless of what was returned.

namespace App\View\Composers;

use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Setting;
use App\Services\AssetReplacement;
use App\Services\AssetShipments;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view): void
    {
        // Guard against the setup wizard, where DB tables may not exist yet
        try {
            $settings = Setting::getSettings();
        } catch (\Exception $e) {
            Log::debug($e);

            return;
        }

        try {
            // Nine counts run on every page: keep them a few minutes per
            // person (what each one sees depends on their permissions and
            // company). Saving an asset, shipment or return starts afresh.
            $key = 'sidebar-counts:'.(auth()->id() ?? 0).':'.Cache::get(self::VERSION_KEY, 0);
            $view->with(Cache::remember($key, now()->addMinutes(self::MINUTES), fn () => $this->counts($settings)));
        } catch (\Exception $e) {
            Log::debug($e);
        }
    }

    /** Cache key whose value changes whenever the counted records change. */
    public const VERSION_KEY = 'sidebar-counts-version';

    /** Minutes the counts are kept (dates move on even when nothing is saved). */
    public const MINUTES = 5;

    /** Make every person's next page count again. */
    public static function forget(): void
    {
        Cache::forever(self::VERSION_KEY, (string) microtime(true));
    }

    /**
     * @return array<string, int>
     */
    private function counts(Setting $settings): array
    {
        $due_for_checkin = Asset::DueForCheckin($settings)->count();
        $overdue_for_checkin = Asset::OverdueForCheckin()->count();
        $due_for_audit = Asset::DueForAudit($settings)->count();
        $overdue_for_audit = Asset::OverdueForAudit()->count();

        return [
            'total_assets' => Asset::AssetsForShow()->count(),
            'total_due_for_audit' => $due_for_audit,
            'total_overdue_for_audit' => $overdue_for_audit,
            'total_due_for_checkin' => $due_for_checkin,
            'total_overdue_for_checkin' => $overdue_for_checkin,
            'total_due_and_overdue_for_checkin' => $due_for_checkin + $overdue_for_checkin,
            'total_due_and_overdue_for_audit' => $due_for_audit + $overdue_for_audit,
        ] + $this->trackingCounts();
    }

    /**
     * Counts shown beside the Tracking entries of the side menu: open
     * shipments (amber when one is due or late) and assets whose mandatory
     * replacement is overdue or due within 15 days.
     *
     * @return array{nav_open_shipments: int, nav_due_shipments: int, nav_replacement_due: int, nav_open_returns: int}
     */
    private function trackingCounts(): array
    {
        $user = auth()->user();
        if (! $user || ! Setting::inventoryModuleEnabled('assets') || Gate::denies('index', Asset::class)) {
            return ['nav_open_shipments' => 0, 'nav_due_shipments' => 0, 'nav_replacement_due' => 0, 'nav_open_returns' => 0];
        }

        return [
            'nav_open_shipments' => AssetShipments::visibleTo($user)->open()->count(),
            'nav_due_shipments' => AssetShipments::dueFor($user)->count(),
            'nav_replacement_due' => AssetReplacement::query([null, today()->addDays(15)->toDateString()])->count(),
            'nav_open_returns' => AssetReturn::open()->count(),
        ];
    }
}
