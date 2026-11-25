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
        // Every 6 hours (01:30, 07:30, 13:30, 19:30): at most 6 hours of work
        // can be lost. The dump is taken in a single transaction, so people
        // can keep working while it runs.
        $schedule->command('snipeit:backup')
            ->cron('30 1,7,13,19 * * *')
            ->withoutOverlapping();
        $schedule->command('backup:clean')->dailyAt('02:30');
        // Warns by mail (MAIL_BACKUP_NOTIFICATION_*) when the newest backup on
        // any destination is missing or older than a day.
        $schedule->command('snipeit:backup-monitor')->dailyAt('08:00');
        // Sends once a day, at the hour chosen in Settings → Email.
        $schedule->command('snipeit:daily-digest')->hourlyAt(5)->withoutOverlapping();
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
