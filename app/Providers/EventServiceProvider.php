<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\UserActivityLogged::class => [
            \App\Listeners\LogUserActivity::class,
        ],
    ];

    public function boot()
    {
        //
    }
}
