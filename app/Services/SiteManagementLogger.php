<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class SiteManagementLogger
{
    protected $logger;

    public function __construct()
    {
        $this->logger = new Logger('site-management');
        $handler = new RotatingFileHandler(
            storage_path('logs/site-management.log'),
            365, // Keep 365 days (1 year) of logs
            Logger::INFO
        );

        // Set custom filename format for monthly rotation
        $handler->setFilenameFormat('site-management-{date}', 'Y-m');

        $this->logger->pushHandler($handler);
    }

    /**
     * Log site-related operations
     */
    public function logSite(string $action, array $data, string $level = 'info'): void
    {
        $this->log('site', $action, $data, $level);
    }

    /**
     * Log page-related operations
     */
    public function logPage(string $action, array $data, string $level = 'info'): void
    {
        $this->log('page', $action, $data, $level);
    }

    /**
     * Log export-related operations
     */
    public function logExport(string $action, array $data, string $level = 'info'): void
    {
        $this->log('export', $action, $data, $level);
    }

    /**
     * Log deployment-related operations
     */
    public function logDeploy(string $action, array $data, string $level = 'info'): void
    {
        $this->log('deploy', $action, $data, $level);
    }

    /**
     * Generic logging method
     */
    protected function log(string $category, string $action, array $data, string $level): void
    {
        $context = array_merge([
            'category' => $category,
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
        ], $data);

        $message = "[{$category}] {$action}";

        switch ($level) {
            case 'error':
                $this->logger->error($message, $context);
                break;
            case 'warning':
                $this->logger->warning($message, $context);
                break;
            default:
                $this->logger->info($message, $context);
        }
    }
}