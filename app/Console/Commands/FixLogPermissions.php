<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixLogPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:fix-permissions {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix permissions for log files to prevent permission denied errors';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $logsPath = storage_path('logs');
        $webUser = 'modobomMDB';
        $webGroup = 'modobomMDB';

        $this->info("Checking log file permissions in: {$logsPath}");

        if ($isDryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
        }

        // Check if running as root
        $currentUser = posix_getpwuid(posix_geteuid())['name'] ?? 'unknown';
        $this->info("Running as user: {$currentUser}");

        if (!is_dir($logsPath)) {
            $this->error("Logs directory does not exist: {$logsPath}");
            return 1;
        }

        // Get all log files
        $logFiles = glob($logsPath . '/*.log');
        $this->info("Found " . count($logFiles) . " log files");

        $changes = 0;

        // Check directory permissions
        $dirPerms = substr(sprintf('%o', fileperms($logsPath)), -3);
        if ($dirPerms !== '777') {
            $this->line("Directory permissions: {$dirPerms} (should be 777)");
            if (!$isDryRun) {
                chmod($logsPath, 0777);
                $this->info("✓ Fixed directory permissions");
            } else {
                $this->info("Would fix directory permissions");
            }
            $changes++;
        } else {
            $this->info("✓ Directory permissions are correct (777)");
        }

        // Check and fix file permissions and ownership
        foreach ($logFiles as $file) {
            $filename = basename($file);
            $perms = substr(sprintf('%o', fileperms($file)), -3);
            $owner = posix_getpwuid(fileowner($file))['name'] ?? 'unknown';
            $group = posix_getgrgid(filegroup($file))['name'] ?? 'unknown';

            $needsPermissionFix = $perms !== '777';
            $needsOwnershipFix = ($owner !== $webUser || $group !== $webGroup);

            if ($needsPermissionFix || $needsOwnershipFix) {
                $this->line("File: {$filename}");
                
                if ($needsPermissionFix) {
                    $this->line("  Permissions: {$perms} (should be 777)");
                    if (!$isDryRun) {
                        chmod($file, 0777);
                        $this->info("  ✓ Fixed file permissions");
                    } else {
                        $this->info("  Would fix file permissions");
                    }
                }

                if ($needsOwnershipFix) {
                    $this->line("  Ownership: {$owner}:{$group} (should be {$webUser}:{$webGroup})");
                    if (!$isDryRun && $currentUser === 'root') {
                        chown($file, $webUser);
                        chgrp($file, $webGroup);
                        $this->info("  ✓ Fixed file ownership");
                    } else if (!$isDryRun) {
                        $this->warn("  Cannot fix ownership (not running as root)");
                    } else {
                        $this->info("  Would fix file ownership");
                    }
                }
                
                $changes++;
            } else {
                $this->info("✓ {$filename} has correct permissions and ownership");
            }
        }

        if ($changes === 0) {
            $this->info("All log files have correct permissions!");
        } else {
            $message = $isDryRun ? "Would make {$changes} changes" : "Made {$changes} changes";
            $this->info($message);
        }

        // Add recommendation for cron job
        if (!$isDryRun && $changes > 0) {
            $this->info("\nTo prevent this issue in the future, consider adding this to your crontab:");
            $this->line("0 2 * * * cd " . base_path() . " && php artisan logs:fix-permissions > /dev/null 2>&1");
        }

        return 0;
    }
} 