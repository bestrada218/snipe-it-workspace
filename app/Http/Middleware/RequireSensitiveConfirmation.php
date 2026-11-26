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

    public const INTENDED = 'sensitive_intended';

    /** Minutes a pass or a page token stays valid. */
    public const MINUTES = 15;

    public function handle(Request $request, Closure $next, string $scope): Response
    {
        $session = $request->session();

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            $pass = $session->pull(self::SESSION_KEY);
            if (! $this->isFresh($request, $pass, $scope)) {
                $route = $request->route();
                $parameters = array_intersect_key($route->parameters(), array_flip($route->parameterNames()));

                return $this->challenge($request, $scope, $parameters);
            }

            $token = Str::random(40);
            $session->put(self::PAGE_TOKEN, ['user' => $request->user()->id, 'at' => now()->getTimestamp(), 'token' => $token, 'scope' => $scope]);
            $request->attributes->set(self::FIELD, $token);

            return $next($request);
        }

        $page = $session->get(self::PAGE_TOKEN);
        $token = $request->input(self::FIELD);
        if (! $this->isFresh($request, $page, $scope) || ! is_string($token)
            || ! is_string($page['token'] ?? null) || ! hash_equals($page['token'], $token)) {
            return $this->challenge($request, $scope);
        }

        $session->forget(self::PAGE_TOKEN);
        $response = $next($request);
        self::grant($request, $scope);

        return $response;
    }

    /** Leave a one-time pass for the next opening of the page. */
    public static function grant(Request $request, string $scope): void
    {
        $request->session()->put(self::SESSION_KEY, ['user' => $request->user()->id, 'at' => now()->getTimestamp(), 'scope' => $scope]);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function challenge(Request $request, string $scope, array $parameters = []): Response
    {
        $request->session()->forget([self::SESSION_KEY, self::PAGE_TOKEN]);
        $request->session()->put(self::INTENDED, ['scope' => $scope, 'parameters' => $parameters]);

        return redirect()->route('sensitive.confirm');
    }

    private function isFresh(Request $request, mixed $entry, string $scope): bool
    {
        return is_array($entry)
            && ($entry['user'] ?? null) === $request->user()?->id
            && ($entry['scope'] ?? null) === $scope
            && ($entry['at'] ?? 0) >= now()->subMinutes(self::MINUTES)->getTimestamp();
    }
}
