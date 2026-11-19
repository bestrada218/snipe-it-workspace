<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Blade::anonymousComponentPath(__DIR__.'/../../resources/views/blade');

        /**
         * Navigation guard: the gate alone is not enough, because report pages
         * temporarily grant read access to disabled inventory modules so their
         * history stays reportable. Menus must still hide those modules.
         */
        Blade::if('canBrowse', fn (string $ability, string $model): bool => Gate::allows($ability, $model)
            && Setting::inventoryModelEnabled($model));
    }
}
