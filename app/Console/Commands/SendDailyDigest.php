<?php

namespace App\Console\Commands;

use App\Mail\DailyDigestMail;
use App\Services\Mail\DailyDigest;
use App\Services\Mail\MailSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the daily summary at the hour chosen in Settings → Email. The
 * scheduler calls it every hour; it sends once a day, at that hour, and
 * only when something needs attention.
 */
class SendDailyDigest extends Command
{
    protected $signature = 'snipeit:daily-digest {--now : Send now, whatever the hour and even with nothing to report}';

    protected $description = 'Email the daily summary of what needs attention to the people chosen in Settings → Email.';

    public function handle(): int
    {
        $digest = MailSettings::digest();
        $recipients = $digest['recipients'] ?? [];
        $now = $this->option('now');

        if (! ($digest['enabled'] ?? false) || $recipients === []) {
            $this->info('The daily summary is off or has no recipients.');

            return self::SUCCESS;
        }

        if (! $now) {
            $last = MailSettings::status()['digest']['at'] ?? null;
            if ((int) now()->format('G') !== (int) ($digest['hour'] ?? 7) || ($last && substr($last, 0, 10) === now()->toDateString())) {
                return self::SUCCESS;
            }
        }

        $sections = DailyDigest::sections();
        if ($sections === [] && ! $now) {
            MailSettings::recordStatus('digest', null, ['sent' => false]);
            $this->info('Nothing needs attention today; no summary sent.');

            return self::SUCCESS;
        }

        try {
            Mail::bcc($recipients)->send(new DailyDigestMail($sections));
        } catch (Throwable $error) {
            MailSettings::recordStatus('digest', trans('mail-settings.transport_problem'), ['sent' => false]);
            Log::warning('Could not send the daily summary.', ['type' => $error::class]);
            $this->error('The daily summary could not be sent.');

            return self::FAILURE;
        }

        MailSettings::recordStatus('digest', null, ['sent' => true, 'to' => count($recipients)]);
        $this->info('Daily summary sent to '.count($recipients).' recipient(s).');

        return self::SUCCESS;
    }
}
