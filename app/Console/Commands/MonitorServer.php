<?php

namespace App\Console\Commands;

use App\Repositories\MonitorServerRepository;
use App\Repositories\ServerRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class MonitorServer extends Command
{
    protected $signature = 'monitor:server';
    protected $description = 'Monitor stats of server';

    public function __construct(MonitorServerRepository $monitorServerRepository, ServerRepository $serverRepository)
    {
        parent::__construct();
        $this->serverRepository = $serverRepository;
        $this->monitorServerRepository = $monitorServerRepository;
    }

    public function handle()
    {
        try {

            $ip = getHostByName(getHostName());
            $server = $this->serverRepository->getByIp($ip);

            if (!$server) {
                $this->error('Server not found');
                return;
            }

            $stats = [
                'server_id' => $server->id,
                'cpu' => $this->getCpuUsage(),
                'memory' => $this->getMemoryUsage(),
                'disk' => $this->getDiskUsage(),
                'services' => $this->getServiceStatus(),
                'logs' => $this->getLatestLogs(),
                'timestamp' => now()->toDateTimeString(),
            ];

            Redis::publish('monitor-channel', json_encode($stats));
            $this->monitorServerRepository->create($stats);

            dump(json_encode($stats, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            Log::error('Error collecting server metrics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Error: ' . $e->getMessage());
        }
    }

    private function getCpuUsage()
    {
        try {
            $load = sys_getloadavg();
            return round($load[0], 2);
        } catch (\Exception $e) {
            Log::warning('Failed to get CPU usage', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function getMemoryUsage()
    {
        try {
            $free = shell_exec('free -m');
            if (!$free) {
                throw new \Exception('Failed to execute free command');
            }
            $lines = explode("\n", $free);
            $data = preg_split('/\s+/', trim($lines[1]));
            $total = (int) $data[1];
            $used = (int) $data[2];
            return $total ? round(($used / $total) * 100, 2) : null;
        } catch (\Exception $e) {
            Log::warning('Failed to get memory usage', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function getDiskUsage()
    {
        try {
            $total = disk_total_space(base_path());
            $free = disk_free_space(base_path());
            if ($total === false || $free === false) {
                throw new \Exception('Failed to get disk space');
            }
            $used = $total - $free;
            return $total ? round(($used / $total) * 100, 2) : null;
        } catch (\Exception $e) {
            Log::warning('Failed to get disk usage', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function getServiceStatus()
    {
        $services = ['nginx', 'redis-server', 'php8.4-fpm', 'mysql', 'mongodb'];
        $status = [];

        foreach ($services as $service) {
            try {
                $output = shell_exec("systemctl is-active $service 2>/dev/null");
                $status[$service] = trim($output) === 'active';
            } catch (\Exception $e) {
                Log::warning("Failed to get status for $service", ['error' => $e->getMessage()]);
                $status[$service] = false;
            }
        }

        return $status;
    }

    private function getLatestLogs()
    {
        $logs = [];
        $laravelLog = storage_path('logs/laravel.log');
        $systemLog = '/var/log/syslog';

        try {
            if (file_exists($laravelLog)) {
                $lines = array_slice(file($laravelLog), -5);
                $logs['laravel'] = array_map('trim', $lines);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get Laravel logs', ['error' => $e->getMessage()]);
        }

        try {
            if (file_exists($systemLog)) {
                $lines = array_slice(file($systemLog), -5);
                $logs['system'] = array_map('trim', $lines);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get system logs', ['error' => $e->getMessage()]);
        }

        return $logs;
    }
}
