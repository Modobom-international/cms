<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class ApplicationLogger
{
    protected $logger;
    protected $domainLogger;

    public function __construct()
    {
        // General site management logger
        $this->logger = new Logger('site-management');
        $handler = new RotatingFileHandler(
            storage_path('logs/site-management.log'),
            365, // Keep 365 days (1 year) of logs
            Logger::INFO
        );

        // Set custom filename format for monthly rotation
        $handler->setFilenameFormat('site-management-{date}', 'Y-m');

        $this->logger->pushHandler($handler);

        // Separate domain logger
        $this->domainLogger = new Logger('domain-management');
        $domainHandler = new RotatingFileHandler(
            storage_path('logs/domain-management.log'),
            365, // Keep 365 days (1 year) of logs
            Logger::INFO
        );

        // Set custom filename format for monthly rotation
        $domainHandler->setFilenameFormat('domain-management-{date}', 'Y-m');

        $this->domainLogger->pushHandler($domainHandler);
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
     * Log domain-related operations (uses separate domain logger)
     */
    public function logDomain(string $action, array $data, string $level = 'info'): void
    {
        $context = array_merge([
            'category' => 'domain',
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
        ], $data);

        $message = "[domain] {$action}";

        switch ($level) {
            case 'error':
                $this->domainLogger->error($message, $context);
                break;
            case 'warning':
                $this->domainLogger->warning($message, $context);
                break;
            default:
                $this->domainLogger->info($message, $context);
        }
    }

    /**
     * Generic logging method (for non-domain operations)
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