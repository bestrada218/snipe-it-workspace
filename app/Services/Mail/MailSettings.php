<?php

namespace App\Services\Mail;

use App\Models\MailSetting;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Outgoing mail set up from Settings → Email instead of the .env file.
 *
 * When it is turned on, apply() (run on every boot) points Laravel's SMTP
 * mailer and the sender address at the saved server, so every mail the app
 * sends (alerts, acceptances, password resets, the daily summary) uses it.
 * When it is off, the .env settings stay in charge.
 */
class MailSettings
{
    /**
     * Common providers: choosing one fills the server and port. Each has a
     * step-by-step guide in the UI (mail-settings.providers in the lang files).
     *
     * @var array<string, array{host?: string, port?: int, username?: string}>
     */
    public const PROVIDERS = [
        'google' => ['host' => 'smtp.gmail.com', 'port' => 587],
        'microsoft' => ['host' => 'smtp.office365.com', 'port' => 587],
        'sendgrid' => ['host' => 'smtp.sendgrid.net', 'port' => 587, 'username' => 'apikey'],
        'ses' => ['host' => 'email-smtp.us-east-1.amazonaws.com', 'port' => 587],
        'mailgun' => ['host' => 'smtp.mailgun.org', 'port' => 587],
        'other' => [],
    ];

    private const CACHE_KEY = 'mail-settings-row';

    /**
     * @return array{enabled?: bool, provider?: string, host?: string, port?: int, username?: string, password?: ?string, from_address?: string, from_name?: string, reply_to?: string}
     */
    public static function saved(): array
    {
        try {
            return self::row()?->settings ?? [];
        } catch (DecryptException) {
            return [];
        }
    }

    public static function unreadable(): bool
    {
        try {
            self::row()?->settings;

            return false;
        } catch (DecryptException) {
            return true;
        }
    }

    public static function enabled(): bool
    {
        return (bool) (self::saved()['enabled'] ?? false);
    }

    /**
     * Point the mailer at the saved server. Runs on every boot, so it reads
     * the encrypted value from the cache (never the plain password) and
     * gives up quietly on a fresh install with no table yet.
     */
    public static function apply(): void
    {
        try {
            $raw = Cache::rememberForever(self::CACHE_KEY, fn () => (string) (DB::table('mail_settings')->value('settings') ?? ''));
            if ($raw === '') {
                return;
            }
            $settings = json_decode(Crypt::decryptString($raw), true) ?: [];
        } catch (Throwable) {
            return;
        }

        if (! ($settings['enabled'] ?? false) || blank($settings['host'] ?? null)) {
            return;
        }

        $port = (int) ($settings['port'] ?? 587);
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $settings['host'],
            'mail.mailers.smtp.port' => $port,
            // 465 is TLS from the first byte; 587 and 25 upgrade with STARTTLS.
            'mail.mailers.smtp.scheme' => $port === 465 ? 'smtps' : 'smtp',
            'mail.mailers.smtp.username' => $settings['username'] ?? null,
            'mail.mailers.smtp.password' => $settings['password'] ?? null,
            'mail.from.address' => $settings['from_address'] ?? config('mail.from.address'),
            'mail.from.name' => $settings['from_name'] ?? config('mail.from.name'),
        ]);
        if (filled($settings['reply_to'] ?? null)) {
            config(['mail.reply_to.address' => $settings['reply_to'], 'mail.reply_to.name' => $settings['from_name'] ?? null]);
        }

        // A mailer built before this point keeps the old server otherwise.
        if (app()->resolved('mail.manager')) {
            Mail::purge('smtp');
        }
    }

    /**
     * Save the server. A blank password keeps the one already saved.
     *
     * @param  array<string, mixed>  $input
     */
    public static function save(array $input, User $actor): void
    {
        $saved = self::saved();
        $row = self::row() ?? new MailSetting;
        $row->settings = [
            'enabled' => (bool) ($input['enabled'] ?? false),
            'provider' => array_key_exists($input['provider'] ?? '', self::PROVIDERS) ? $input['provider'] : 'other',
            'host' => trim((string) ($input['host'] ?? '')),
            'port' => (int) ($input['port'] ?? 587),
            'username' => trim((string) ($input['username'] ?? '')),
            'password' => filled($input['password'] ?? null) ? (string) $input['password'] : ($saved['password'] ?? null),
            'from_address' => trim((string) ($input['from_address'] ?? '')),
            'from_name' => trim((string) ($input['from_name'] ?? '')),
            'reply_to' => trim((string) ($input['reply_to'] ?? '')),
        ];
        $row->updated_by = $actor->id;
        $row->save();
        Cache::forget(self::CACHE_KEY);
    }

    public static function hasPassword(): bool
    {
        return filled(self::saved()['password'] ?? null);
    }

    /**
     * Send a short test message with the saved server.
     *
     * @return string|null why it failed, or null when it was sent
     */
    public static function sendTest(string $to): ?string
    {
        self::apply();

        try {
            Mail::raw(trans('mail-settings.test_body', ['site' => config('app.name')]), function ($message) use ($to) {
                $message->to($to)->subject(trans('mail-settings.test_subject'));
            });
            $error = null;
        } catch (Throwable $exception) {
            $error = Str::limit($exception->getMessage(), 500);
            Log::warning('Test email failed: '.$error);
        }

        self::recordStatus('test', $error, ['to' => $to]);

        return $error;
    }

    /**
     * @return array{enabled?: bool, recipients?: list<string>, hour?: int}
     */
    public static function digest(): array
    {
        return self::row()?->digest ?? [];
    }

    /**
     * @param  array{enabled?: mixed, recipients?: list<string>, hour?: mixed}  $digest
     */
    public static function saveDigest(array $digest, User $actor): void
    {
        $row = self::row() ?? new MailSetting;
        $row->digest = [
            'enabled' => (bool) ($digest['enabled'] ?? false),
            'recipients' => array_values(array_unique($digest['recipients'] ?? [])),
            'hour' => max(0, min(23, (int) ($digest['hour'] ?? 7))),
        ];
        $row->updated_by = $actor->id;
        $row->save();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public static function recordStatus(string $what, ?string $error, array $extra = []): void
    {
        try {
            $row = self::row() ?? new MailSetting;
            $status = $row->status ?? [];
            $status[$what] = ['at' => now()->toIso8601String(), 'ok' => $error === null, 'error' => $error] + $extra;
            $row->status = $status;
            $row->save();
        } catch (Throwable $exception) {
            Log::warning('Could not record the mail status: '.$exception->getMessage());
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function status(): array
    {
        return self::row()?->status ?? [];
    }

    public static function row(): ?MailSetting
    {
        try {
            return MailSetting::query()->first();
        } catch (Throwable) {
            return null;
        }
    }
}
