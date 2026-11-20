<?php

namespace App\Console;

use App\Models\Setting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        if (Setting::getSettings()?->alerts_enabled === 1) {
            $schedule->command('snipeit:inventory-alerts')->daily();
            $schedule->command('snipeit:expiring-alerts')->daily();
            $schedule->command('snipeit:expected-checkin')->daily();
            $schedule->command('snipeit:upcoming-audits')->daily();
        }
        // Daily, before the inventory sync at 03:00: shipments, duplicate
        // approvals and damage reports change every day, and a weekly
        // backup could lose a full week of that history.
        $schedule->command('snipeit:backup')
            ->dailyAt('01:30')
            ->withoutOverlapping();
        $schedule->command('backup:clean')->dailyAt('02:30');
        $schedule->command('auth:clear-resets')->everyFifteenMinutes();
        $schedule->command('saml:clear_expired_nonces')->weekly();

        $schedule->command('snipeit:pull-inventory')
            ->daily()
            ->withoutOverlapping();

        // Push runs a few hours offset from the pull so the two
        // don't stack on a single Laravel scheduler tick if a slow
        // adapter's pull runs long.
        $schedule->command('snipeit:push-inventory')
            ->dailyAt('03:00')
            ->withoutOverlapping();
    }

    /**
     * This method is required by Laravel to handle any console routes
     * that are defined in routes/console.php.
     */
    protected function commands()
    {
        require base_path('routes/console.php');
        $this->load(__DIR__.'/Commands');
    }
}
