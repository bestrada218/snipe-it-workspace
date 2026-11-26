<?php

namespace App\Http\Middleware;

use App\Helpers\Helper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceApiTwoFactorEnrollment
{
    public function handle(Request $request, Closure $next)
    {
        // If auth:api didn't populate a user (bad token, no token), let it
        // produce the standard 401 rather than emitting a 403 here.
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        if (! $user->two_factor_active()) {
            return $next($request);
        }

        // Stateless bearer tokens can't prompt for a live TOTP code, so the
        // strongest guarantee we can enforce is that the token's owner has
        // provisioned and confirmed a TOTP secret. A PAT owned by a not-yet-
        // enrolled user gets refused so that setting two_factor_enabled = 2
        // actually gates the API surface (previously it only gated the web
        // group, leaving already-issued PATs fully live). Web session auth
        // still flows through CheckForTwoFactor and remains untouched.
        if ((string) $user->two_factor_enrolled !== '1') {
            return response()->json(
                Helper::formatStandardApiResponse('error', null, trans('auth/message.two_factor.please_enroll')),
                Response::HTTP_FORBIDDEN,
            );
        }

        return $next($request);
    }
}
