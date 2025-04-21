<?php

namespace App\Traits;

use App\Services\ActivityLogger;

trait LogsActivity
{
    protected function logActivity(string $action, array $details = [], ?string $description = null, ?int $userId = null): void
    {
        app(ActivityLogger::class)->log($action, $details, $description, $userId);
    }
}
