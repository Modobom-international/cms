<?php

namespace App\Services;

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

    public function logExportCsv(string $fileName, array $extraDetails = []): void
    {
        $this->log('export_csv', array_merge([
            'file_name' => $fileName,
        ], $extraDetails));
    }

    public function logViewReport(string $reportType, array $extraDetails = []): void
    {
        $this->log('view_report', array_merge([
            'report_type' => $reportType,
        ], $extraDetails));
    }
}
