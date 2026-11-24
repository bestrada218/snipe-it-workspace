<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages behind this middleware ask for the password and a two-factor code
 * every time they are opened: a confirmation lets the page open once.
 *
 * - Confirming (SensitiveConfirmationController) leaves a one-time pass.
 * - Opening the page (GET) uses the pass up and gives the page a token for
 *   its own forms. Leaving and coming back, or reloading, asks again.
 * - A form sent from that page with its token (save, test connection)
 *   leaves a new one-time pass, so the page it redirects to opens.
 */
class RequireSensitiveConfirmation
{
    /** One-time pass to open the page. */
    public const SESSION_KEY = 'sensitive_confirmed';

    /** Token the open page puts in its forms. */
    public const PAGE_TOKEN = 'sensitive_page_token';

    /** Form field that carries the page token. */
    public const FIELD = '_sensitive';

    /** Minutes a pass or a page token stays valid. */
    public const MINUTES = 15;

    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();

        if ($request->isMethod('GET')) {
            $pass = $session->pull(self::SESSION_KEY);
            if (! $this->isFresh($request, $pass)) {
                $session->put('url.sensitive_intended', $request->fullUrl());

                return redirect()->route('sensitive.confirm');
            }

            $token = Str::random(40);
            $session->put(self::PAGE_TOKEN, ['user' => $request->user()->id, 'at' => now()->getTimestamp(), 'token' => $token]);
            $request->attributes->set(self::FIELD, $token);

            return $next($request);
        }

        $page = $session->get(self::PAGE_TOKEN);
        if (! $this->isFresh($request, $page) || ! hash_equals((string) ($page['token'] ?? ''), (string) $request->input(self::FIELD))) {
            return redirect()->route('sensitive.confirm');
        }

        $response = $next($request);
        self::grant($request);

        return $response;
    }

    /** Leave a one-time pass for the next opening of the page. */
    public static function grant(Request $request): void
    {
        $request->session()->put(self::SESSION_KEY, ['user' => $request->user()->id, 'at' => now()->getTimestamp()]);
    }

    /**
     * @param  mixed  $entry
     */
    private function isFresh(Request $request, $entry): bool
    {
        return is_array($entry)
            && ($entry['user'] ?? null) === $request->user()?->id
            && ($entry['at'] ?? 0) >= now()->subMinutes(self::MINUTES)->getTimestamp();
    }
}
