<?php

namespace App\Http\Controllers;

use App\Services\Mail\MailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Settings → Email: the server the app sends mail through, a test message
 * and the daily summary. Superadmins only, behind a fresh password and
 * two-factor check (it holds the mail account's password).
 */
class MailSettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.mail', [
            'saved' => MailSettings::saved(),
            'digest' => MailSettings::digest(),
            'status' => MailSettings::status(),
            'hasPassword' => MailSettings::hasPassword(),
            'unreadable' => MailSettings::unreadable(),
            'row' => MailSettings::row(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if (config('app.lock_passwords')) {
            return redirect()->route('settings.mail.index')->with('error', trans('general.feature_disabled'));
        }

        $input = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'provider' => ['required', Rule::in(array_keys(MailSettings::PROVIDERS))],
            'host' => ['required_if:enabled,1', 'nullable', 'string', 'max:255'],
            'port' => ['required_if:enabled,1', 'nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:500'],
            'from_address' => ['required_if:enabled,1', 'nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:100'],
            'reply_to' => ['nullable', 'email', 'max:255'],
        ], [], [
            'host' => trans('mail-settings.host'),
            'port' => trans('mail-settings.port'),
            'from_address' => trans('mail-settings.from_address'),
        ]);

        MailSettings::save($input, $request->user());
        Log::info("Mail settings changed by user {$request->user()->id}.");

        return redirect()->route('settings.mail.index')->with('success', trans('mail-settings.saved'));
    }

    public function test(Request $request): RedirectResponse
    {
        $input = $request->validate(['test_to' => ['required', 'email']]);
        $error = MailSettings::sendTest($input['test_to']);

        return redirect()->route('settings.mail.index')->with(
            $error ? 'error' : 'success',
            $error ? trans('mail-settings.test_failed', ['error' => $error]) : trans('mail-settings.test_sent', ['to' => $input['test_to']])
        );
    }

    public function updateDigest(Request $request): RedirectResponse
    {
        $recipients = collect(preg_split('/[\s,;]+/', (string) $request->input('recipients'), -1, PREG_SPLIT_NO_EMPTY))->values();
        $request->merge(['recipient_list' => $recipients->all()]);

        $input = $request->validate([
            'digest_enabled' => ['nullable', 'boolean'],
            'recipient_list' => ['required_if:digest_enabled,1', 'array', 'max:50'],
            'recipient_list.*' => ['email'],
            'hour' => ['required', 'integer', 'min:0', 'max:23'],
        ], [
            'recipient_list.required_if' => trans('mail-settings.digest_recipients_required'),
            'recipient_list.*.email' => trans('mail-settings.digest_recipient_invalid'),
        ]);

        MailSettings::saveDigest([
            'enabled' => $input['digest_enabled'] ?? false,
            'recipients' => $input['recipient_list'] ?? [],
            'hour' => $input['hour'],
        ], $request->user());

        return redirect()->route('settings.mail.index')->with('success', trans('mail-settings.digest_saved'));
    }

    public function sendDigest(): RedirectResponse
    {
        $exit = Artisan::call('snipeit:daily-digest', ['--now' => true]);
        $status = MailSettings::status()['digest'] ?? [];

        return redirect()->route('settings.mail.index')->with(
            $exit === 0 && ($status['ok'] ?? false) ? 'success' : 'error',
            $exit === 0 && ($status['ok'] ?? false)
                ? trans('mail-settings.digest_sent')
                : trans('mail-settings.test_failed', ['error' => $status['error'] ?? trans('mail-settings.digest_off')])
        );
    }
}
