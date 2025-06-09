<?php

namespace App\Services;

use InfluxDB2\Client;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Point;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InfluxDBService
{
    protected $client;
    protected $bucket;
    protected $org;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->bucket = env('INFLUXDB_BUCKET', 'server_monitoring');
        $this->org = env('INFLUXDB_ORG', 'modobom');
    }

    /**
     * Store server monitoring data to InfluxDB
     */
    public function storeServerMetrics(array $data): bool
    {
        try {
            $writeApi = $this->client->createWriteApi();
            
            // Convert timestamp to proper format
            $timestamp = isset($data['timestamp']) 
                ? Carbon::parse($data['timestamp'])->timestamp * 1000000000 // nanoseconds
                : time() * 1000000000;

            // CPU metrics
            if (isset($data['cpu']) && is_numeric($data['cpu'])) {
                $cpuPoint = Point::measurement('server_metrics')
                    ->addTag('server_id', (string)$data['server_id'])
                    ->addTag('metric_type', 'cpu')
                    ->addField('value', (float)$data['cpu'])
                    ->time($timestamp);
                $writeApi->write($cpuPoint, WritePrecision::NS, $this->bucket, $this->org);
            }

            // RAM metrics
            if (isset($data['ram']) && is_numeric($data['ram'])) {
                $ramPoint = Point::measurement('server_metrics')
                    ->addTag('server_id', (string)$data['server_id'])
                    ->addTag('metric_type', 'ram')
                    ->addField('value', (float)$data['ram'])
                    ->time($timestamp);
                $writeApi->write($ramPoint, WritePrecision::NS, $this->bucket, $this->org);
            }

            // Disk metrics
            if (isset($data['disk']) && is_numeric($data['disk'])) {
                $diskPoint = Point::measurement('server_metrics')
                    ->addTag('server_id', (string)$data['server_id'])
                    ->addTag('metric_type', 'disk')
                    ->addField('value', (float)$data['disk'])
                    ->time($timestamp);
                $writeApi->write($diskPoint, WritePrecision::NS, $this->bucket, $this->org);
            }

            // Services status
            if (isset($data['services'])) {
                $servicesData = is_string($data['services']) ? json_decode($data['services'], true) : $data['services'];
                if (is_array($servicesData)) {
                    foreach ($servicesData as $serviceName => $status) {
                        $servicePoint = Point::measurement('service_status')
                            ->addTag('server_id', (string)$data['server_id'])
                            ->addTag('service_name', $serviceName)
                            ->addField('status', $status === 'running' ? 1 : 0)
                            ->addField('status_text', $status)
                            ->time($timestamp);
                        $writeApi->write($servicePoint, WritePrecision::NS, $this->bucket, $this->org);
                    }
                }
            }

            // Store logs as events
            if (isset($data['logs'])) {
                $logsData = is_string($data['logs']) ? json_decode($data['logs'], true) : $data['logs'];
                if (is_array($logsData) && !empty($logsData)) {
                    $logPoint = Point::measurement('server_logs')
                        ->addTag('server_id', (string)$data['server_id'])
                        ->addField('log_count', count($logsData))
                        ->addField('logs_sample', json_encode(array_slice($logsData, 0, 10))) // Store sample of logs
                        ->time($timestamp);
                    $writeApi->write($logPoint, WritePrecision::NS, $this->bucket, $this->org);
                }
            }

            $writeApi->close();
            return true;

        } catch (\Exception $e) {
            Log::error('InfluxDB write error: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Query server metrics from InfluxDB
     */
    public function getServerMetrics(int $serverId, string $metricType = null, int $hours = 24): array
    {
        try {
            $queryApi = $this->client->createQueryApi();
            
            $timeRange = Carbon::now()->subHours($hours)->toISOString();
            
            $flux = 'from(bucket: "' . $this->bucket . '")
                |> range(start: ' . $timeRange . ')
                |> filter(fn: (r) => r._measurement == "server_metrics")
                |> filter(fn: (r) => r.server_id == "' . $serverId . '")';

            if ($metricType) {
                $flux .= '|> filter(fn: (r) => r.metric_type == "' . $metricType . '")';
            }

            $flux .= '|> aggregateWindow(every: 5m, fn: mean, createEmpty: false)
                |> yield(name: "mean")';

            $result = $queryApi->query($flux, $this->org);
            
            $data = [];
            foreach ($result as $table) {
                foreach ($table->records as $record) {
                    $data[] = [
                        'time' => $record->getTime(),
                        'metric_type' => $record->values['metric_type'],
                        'value' => $record->getValue(),
                        'server_id' => $record->values['server_id']
                    ];
                }
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('InfluxDB query error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get service status over time
     */
    public function getServiceStatus(int $serverId, int $hours = 24): array
    {
        try {
            $queryApi = $this->client->createQueryApi();
            
            $timeRange = Carbon::now()->subHours($hours)->toISOString();
            
            $flux = 'from(bucket: "' . $this->bucket . '")
                |> range(start: ' . $timeRange . ')
                |> filter(fn: (r) => r._measurement == "service_status")
                |> filter(fn: (r) => r.server_id == "' . $serverId . '")
                |> aggregateWindow(every: 10m, fn: last, createEmpty: false)
                |> yield(name: "last")';

            $result = $queryApi->query($flux, $this->org);
            
            $data = [];
            foreach ($result as $table) {
                foreach ($table->records as $record) {
                    $data[] = [
                        'time' => $record->getTime(),
                        'service_name' => $record->values['service_name'],
                        'status' => $record->getValue(),
                        'status_text' => $record->values['status_text'] ?? ($record->getValue() == 1 ? 'running' : 'stopped'),
                        'server_id' => $record->values['server_id']
                    ];
                }
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('InfluxDB service status query error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Test InfluxDB connection
     */
    public function testConnection(): bool
    {
        try {
            $queryApi = $this->client->createQueryApi();
            $flux = 'buckets() |> limit(n: 1)';
            $result = $queryApi->query($flux, $this->org);
            
            return true;
        } catch (\Exception $e) {
            Log::error('InfluxDB connection test failed: ' . $e->getMessage());
            return false;
        }
    }
} 