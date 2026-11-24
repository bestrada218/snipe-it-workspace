<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RequireSensitiveConfirmation;
use App\Models\Setting;
use App\Models\User;
use Com\Tecnick\Barcode\Barcode;
use Google2FA;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Asks again for the password and a two-factor code before a sensitive
 * page (see RequireSensitiveConfirmation). Two-factor is mandatory here:
 * someone without a device enrolls one on this same page.
 */
class SensitiveConfirmationController extends Controller
{
    private const PENDING_SECRET = 'sensitive_2fa_pending_secret';

    public function show(Request $request): View|RedirectResponse
    {
        if (RequireSensitiveConfirmation::isConfirmed($request)) {
            return redirect($this->intended($request));
        }

        $user = $request->user();
        $barcode = null;
        $secret = null;

        if (! $this->hasTwoFactor($user)) {
            $secret = $request->session()->get(self::PENDING_SECRET) ?: Google2FA::generateSecretKey();
            $request->session()->put(self::PENDING_SECRET, $secret);
            $barcode = (new Barcode)->getBarcodeObj(
                'QRCODE',
                sprintf(
                    'otpauth://totp/%s:%s?secret=%s&issuer=Snipe-IT&period=30',
                    urlencode($this->settingsSiteName()),
                    urlencode($user->username),
                    urlencode($secret)
                ),
                220,
                220,
                'black',
                [-2, -2, -2, -2]
            );
        }

        return view('settings.sensitive-confirm', [
            'barcode' => $barcode,
            'secret' => $secret,
            'minutes' => RequireSensitiveConfirmation::MINUTES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
            'two_factor_code' => ['required', 'string'],
        ]);

        $user = $request->user();
        $enrolling = ! $this->hasTwoFactor($user);
        $secret = $enrolling ? $request->session()->get(self::PENDING_SECRET) : $user->two_factor_secret;
        $code = preg_replace('/\s+/', '', (string) $request->input('two_factor_code'));

        $passwordOk = Hash::check($request->input('password'), (string) $user->password);
        $codeOk = filled($secret) && Google2FA::verifyKey($secret, $code);

        if (! $passwordOk || ! $codeOk) {
            Log::warning("Sensitive page confirmation failed for user {$user->id}.");

            return redirect()->route('sensitive.confirm')
                ->withErrors([$passwordOk ? 'two_factor_code' : 'password' => trans('cloud-backups.confirm_failed')]);
        }

        if ($enrolling) {
            $user->two_factor_secret = $secret;
            $user->two_factor_enrolled = 1;
            $user->two_factor_optin = 1;
            $user->saveQuietly();
            $request->session()->forget(self::PENDING_SECRET);
        }

        $request->session()->put(RequireSensitiveConfirmation::SESSION_KEY, ['user' => $user->id, 'at' => now()->getTimestamp()]);
        Log::info("User {$user->id} confirmed their password and two-factor code for a sensitive page.");

        $response = redirect($this->intended($request));

        return $enrolling ? $response->with('success', trans('cloud-backups.enrolled')) : $response;
    }

    private function hasTwoFactor(User $user): bool
    {
        return strlen((string) $user->two_factor_secret) >= 16 && (int) $user->two_factor_enrolled === 1;
    }

    private function intended(Request $request): string
    {
        $url = (string) $request->session()->pull('url.sensitive_intended', route('settings.cloud_backups.index'));

        // Only this application's own pages.
        return str_starts_with($url, url('/')) ? $url : route('settings.cloud_backups.index');
    }

    private function settingsSiteName(): string
    {
        return (string) (Setting::getSettings()?->site_name ?: config('app.name'));
    }
}
