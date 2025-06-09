<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use InfluxDB2\Client;

class InfluxDBServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function ($app) {
            return new Client([
                'url' => env('INFLUXDB_URL'),
                'token' => env('INFLUXDB_TOKEN'),
                'bucket' => env('INFLUXDB_BUCKET', 'server_monitoring'),
                'org' => env('INFLUXDB_ORG', 'modobom'),
                'precision' => 'ns'
            ]);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
