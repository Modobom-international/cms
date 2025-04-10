<?php

namespace App\Traits;

use App\Services\ActivityLogger;

trait LogsActivity
{
    protected function logActivity(string $action, array $details = [], ?int $userId = null): void
    {
        app(ActivityLogger::class)->log($action, $details, $userId);
    }

    protected function logAccessView(string $reportType, array $extraDetails = []): void
    {
        app(ActivityLogger::class)->logAccessView($reportType, $extraDetails);
    }
}
