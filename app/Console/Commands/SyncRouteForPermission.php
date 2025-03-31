<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class SyncRouteForPermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-route-for-permission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync route for permission';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            if ($route->getName() === null) {
                continue;
            }

            $middleware = $route->middleware();
            if (!in_array('App\Http\Middleware\Authenticate', $middleware)) {
                continue;
            }

            $getPrefix = $route->getPrefix();
            if ($getPrefix == '/admin') {
                continue;
            }

            $explode = explode('/', $getPrefix);
            $prefix = $explode[1];

            Permission::updateOrCreate(
                ['name' => $route->getName()],
                ['prefix' => $prefix],
                ['description' => $route->getActionName()],
            );
        }

        dump('Sync thành công..........');
    }
}
