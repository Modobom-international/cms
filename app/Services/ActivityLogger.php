<?php

namespace App\Services;

use App\Events\UserActivityLogged;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(string $action, array $details = [], string $description, ?int $userId = null, ?string $ip = null): void
    {
        $userId = $userId ?? Auth::id();

        event(new UserActivityLogged(
            action: $action,
            details: array_merge($details, [
                'logged_at' => now()->toDateTimeString(),
            ]),
            description: $description,
            userId: $userId,
            ip: $ip ?? request()->ip()
        ));
    }
}
