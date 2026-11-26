<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        Telescope::hideRequestParameters([
            '_token', '_sensitive', 'password', 'password_confirmation', 'current_password',
            'two_factor_code', 'two_factor_secret', 'SAMLResponse', 'SAMLRequest', 'code',
            'client_secret', 'access_token', 'refresh_token', 'ldaptest_password',
            's3', 'google',
        ]);

        Telescope::hideRequestHeaders([
            'cookie',
            'authorization',
            'proxy-authorization',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in NON-LOCAL environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            if ($user->isSuperUser()) {
                return true;
            }

            return false;
        });
    }
}
