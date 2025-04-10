<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Events\UserActivityLogged;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(string $action, array $details = [], ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();

        event(new UserActivityLogged(
            action: $action,
            details: array_merge($details, [
                'logged_at' => now()->toDateTimeString(),
            ]),
            userId: $userId
        ));
    }

    public function logAccessView(string $reportType, array $extraDetails = []): void
    {
        $this->log(ActivityAction::ACCESS_VIEW, array_merge([
            'report_type' => $reportType,
        ], $extraDetails));
    }
}
