<?php

namespace App\Console\Commands;

use App\Repositories\PermissionRepository;
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

    protected $permissionRepository;

    public function __construct(PermissionRepository $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;

        parent::__construct();
    }

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

            $getPrefix = $route->getPrefix();
            $explode = explode('/', $getPrefix);

            if ($getPrefix == '/admin') {
                continue;
            }

            if (count($explode) < 2) {
                continue;
            }

            if ($explode[1] == 'api') {
                continue;
            }

            $name = $route->getName();
            $prefix = $explode[1];
            $description = $route->getActionName() ?? 'N/A';
            $data = [
                'name' => $name,
                'prefix' => $prefix,
                'description' => $description,
            ];

            $this->permissionRepository->updateOrCreate($data);
        }

        dump('Sync thành công..........');
    }
}
