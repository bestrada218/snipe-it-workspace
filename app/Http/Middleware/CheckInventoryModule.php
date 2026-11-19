<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInventoryModule
{
    private const ROUTES = [
        'assets' => [
            'hardware.*', 'findbytag/hardware', 'models.*', 'statuslabels.*', 'maintenances.*',
            'api.assets.*', 'api.account.assets', 'api.assetmodels.*', 'api.statuslabels.*',
            'api.maintenances.*', 'api.models.*', 'api.users.assetlist',

        ],
        'licenses' => ['licenses.*', 'api.licenses.*', 'api.users.licenselist'],
        'accessories' => ['accessories.*', 'api.accessories.*', 'api.users.accessorieslist'],
        'consumables' => ['consumables.*', 'api.consumables.*', 'api.users.consumableslist'],
        'components' => ['components.*', 'api.components.*'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('api.assets.requested')) {
            return $next($request);
        }

        foreach (self::ROUTES as $module => $routes) {
            if ($request->routeIs(...$routes)) {
                abort_unless(Setting::inventoryModuleEnabled($module), 404);
                break;
            }
        }

        return $next($request);
    }
}
