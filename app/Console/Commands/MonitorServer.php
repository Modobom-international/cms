<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class MonitorServer extends Command
{
    protected $signature = 'monitor:server';
    protected $description = 'Monitor server stats and send via Redis';

    public function handle()
    {
        $stats = [
            'cpu' => $this->getCpuUsage(),
            'memory' => $this->getMemoryUsage(),
            'disk' => $this->getDiskUsage(),
            'temperature' => $this->getTemperature(),
            'services' => $this->getServiceStatus(),
            'logs' => $this->getLatestLogs(),
            'timestamp' => now()->toDateTimeString(),
        ];

        Redis::publish('monitor-channel', json_encode($stats));
    }

    private function getCpuUsage()
    {
        $load = sys_getloadavg();

        return $load[0];
    }

    private function getMemoryUsage()
    {
        $free = shell_exec('free');
        $lines = explode("\n", $free);
        $data = preg_split('/\s+/', $lines[1]);
        $used = $data[2];
        $total = $data[1];

        return round(($used / $total) * 100, 2);
    }

    private function getDiskUsage()
    {
        $total = disk_total_space(base_path());
        $free = disk_free_space(base_path());
        $used = $total - $free;

        return round(($used / $total) * 100, 2);
    }

    private function getTemperature()
    {
        if (file_exists('/sys/class/thermal/thermal_zone0/temp')) {
            $temp = (int) file_get_contents('/sys/class/thermal/thermal_zone0/temp');
            return $temp / 1000;
        }

        return null;
    }

    private function getServiceStatus()
    {
        $services = ['nginx', 'redis-server', 'php-fpm', 'mysql', 'mongodb'];
        $status = [];

        foreach ($services as $service) {
            $output = shell_exec("systemctl is-active $service 2>/dev/null");
            $status[$service] = trim($output) === 'active';
        }

        return $status;
    }

    private function getLatestLogs()
    {
        $logs = [];
        $laravelLog = storage_path('logs/laravel.log');
        $systemLog = '/var/log/syslog';

        if (file_exists($laravelLog)) {
            $lines = array_slice(file($laravelLog), -5);
            $logs['laravel'] = array_map('trim', $lines);
        }

        if (file_exists($systemLog)) {
            $lines = array_slice(file($systemLog), -5);
            $logs['system'] = array_map('trim', $lines);
        }

        return $logs;
    }
}
