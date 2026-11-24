<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages behind this middleware ask again for the password and a two-factor
 * code (enrolling a device first if the person has none), and the
 * confirmation lasts a few minutes. Used for the cloud backup credentials.
 */
class RequireSensitiveConfirmation
{
    public const SESSION_KEY = 'sensitive_confirmed';

    /** Minutes a confirmation lasts. */
    public const MINUTES = 15;

    public function handle(Request $request, Closure $next): Response
    {
        if (self::isConfirmed($request)) {
            return $next($request);
        }

        if ($request->isMethod('GET')) {
            $request->session()->put('url.sensitive_intended', $request->fullUrl());
        }

        return redirect()->route('sensitive.confirm');
    }

    public static function isConfirmed(Request $request): bool
    {
        $confirmed = $request->session()->get(self::SESSION_KEY);

        return is_array($confirmed)
            && ($confirmed['user'] ?? null) === $request->user()?->id
            && ($confirmed['at'] ?? 0) >= now()->subMinutes(self::MINUTES)->getTimestamp();
    }
}
