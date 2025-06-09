<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InfluxDBService;
use App\Repositories\ServerRepository;
use Carbon\Carbon;

class TestInfluxDB extends Command
{
    protected $signature = 'influxdb:test {--generate-data : Generate sample data}';
    protected $description = 'Test InfluxDB connection and optionally generate sample data';

    protected $influxDBService;
    protected $serverRepository;

    public function __construct(InfluxDBService $influxDBService, ServerRepository $serverRepository)
    {
        parent::__construct();
        $this->influxDBService = $influxDBService;
        $this->serverRepository = $serverRepository;
    }

    public function handle()
    {
        $this->info('Testing InfluxDB connection...');

        // Test connection
        if ($this->influxDBService->testConnection()) {
            $this->info('✓ InfluxDB connection successful!');
        } else {
            $this->error('✗ InfluxDB connection failed!');
            return 1;
        }

        // Generate sample data if requested
        if ($this->option('generate-data')) {
            $this->info('Generating sample monitoring data...');
            $this->generateSampleData();
        }

        $this->info('Test completed!');
        return 0;
    }

    private function generateSampleData()
    {
        // Get first server or create a test server
        $server = $this->serverRepository->getByIp('192.168.1.100');
        
        if (!$server) {
            $this->warn('No server found with IP 192.168.1.100. Creating test server...');
            $server = $this->serverRepository->create([
                'name' => 'Test Server',
                'ip' => '192.168.1.100'
            ]);
            $this->info('Test server created with ID: ' . $server->id);
        }

        $this->info('Using server: ' . $server->name . ' (ID: ' . $server->id . ')');

        // Generate data for the last 2 hours
        $now = Carbon::now();
        $dataPoints = 24; // 24 data points over 2 hours (5-minute intervals)

        for ($i = 0; $i < $dataPoints; $i++) {
            $timestamp = $now->copy()->subMinutes($i * 5);
            
            // Generate realistic metric values
            $cpuBase = 30 + sin($i * 0.3) * 15; // CPU oscillates between 15-45%
            $ramBase = 60 + sin($i * 0.2) * 10; // RAM oscillates between 50-70%
            $diskBase = 25 + ($i * 0.1); // Disk slowly increases

            $data = [
                'server_id' => $server->id,
                'cpu' => max(5, min(95, $cpuBase + rand(-5, 5))), // Add some randomness
                'ram' => max(10, min(90, $ramBase + rand(-3, 3))),
                'disk' => max(5, min(85, $diskBase + rand(-2, 2))),
                'services' => [
                    'nginx' => rand(0, 100) > 5 ? 'running' : 'stopped', // 95% uptime
                    'mysql' => rand(0, 100) > 2 ? 'running' : 'stopped', // 98% uptime
                    'redis' => rand(0, 100) > 8 ? 'running' : 'stopped', // 92% uptime
                    'php-fpm' => rand(0, 100) > 3 ? 'running' : 'stopped', // 97% uptime
                ],
                'logs' => [
                    "[{$timestamp->toDateTimeString()}] INFO: Application running normally",
                    "[{$timestamp->toDateTimeString()}] DEBUG: Memory usage within limits",
                    "[{$timestamp->toDateTimeString()}] INFO: Scheduled job completed"
                ],
                'timestamp' => $timestamp->toDateTimeString(),
            ];

            $success = $this->influxDBService->storeServerMetrics($data);
            
            if ($success) {
                $this->line("✓ Data point {$i}/{$dataPoints} stored for {$timestamp->format('H:i:s')}");
            } else {
                $this->error("✗ Failed to store data point {$i}");
            }

            // Small delay to avoid overwhelming InfluxDB
            usleep(100000); // 0.1 second
        }

        $this->info("Generated {$dataPoints} data points for server {$server->name}");
        
        // Test querying the data
        $this->info('Testing data retrieval...');
        
        $metrics = $this->influxDBService->getServerMetrics($server->id, null, 2);
        $serviceStatus = $this->influxDBService->getServiceStatus($server->id, 2);
        
        $this->info('Retrieved ' . count($metrics) . ' metric data points');
        $this->info('Retrieved ' . count($serviceStatus) . ' service status data points');

        // Display sample results
        if (!empty($metrics)) {
            $this->info('Sample metric data:');
            $sampleMetric = $metrics[0];
            $this->line("  Time: {$sampleMetric['time']}");
            $this->line("  Type: {$sampleMetric['metric_type']}");
            $this->line("  Value: {$sampleMetric['value']}");
        }

        if (!empty($serviceStatus)) {
            $this->info('Sample service status:');
            $sampleService = $serviceStatus[0];
            $this->line("  Time: {$sampleService['time']}");
            $this->line("  Service: {$sampleService['service_name']}");
            $this->line("  Status: {$sampleService['status_text']}");
        }
    }
} 