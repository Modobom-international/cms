<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\MonitorServerRepository;
use App\Repositories\ServerRepository;
use App\Services\InfluxDBService;
use App\Enums\ActivityAction;
use App\Enums\Utility;
use App\Traits\LogsActivity;
use Exception;

class MonitorServerController extends Controller
{
    use LogsActivity;

    protected $serverRepository;
    protected $monitorServerRepository;
    protected $influxDBService;
    protected $utility;

    public function __construct(
        MonitorServerRepository $monitorServerRepository, 
        ServerRepository $serverRepository, 
        InfluxDBService $influxDBService,
        Utility $utility
    ) {
        $this->serverRepository = $serverRepository;
        $this->monitorServerRepository = $monitorServerRepository;
        $this->influxDBService = $influxDBService;
        $this->utility = $utility;
    }

    public function detail(Request $request)
    {
        try {
            $input = $request->all();
            $pageSize = $request->get('pageSize') ?? 10;
            $page = $request->get('page') ?? 1;
            $ip = $request->get('ip');
            $hours = $request->get('hours') ?? 24; // Time range for InfluxDB query

            $server = $this->serverRepository->getByIp($ip);

            if (empty($server)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server không tồn tại',
                    'type' => 'server_empty',
                ], 400);
            }

            // Get paginated data from traditional database
            $query = $this->monitorServerRepository->getByServer($server->id);
            $data = $this->utility->paginate($query, $pageSize, $page);

            // Get time-series data from InfluxDB
            $timeSeriesData = [
                'metrics' => $this->influxDBService->getServerMetrics($server->id, null, $hours),
                'services' => $this->influxDBService->getServiceStatus($server->id, $hours)
            ];

            $this->logActivity(
                ActivityAction::DETAIL_MONITOR_SERVER, 
                ['filters' => $input], 
                'Kiểm tra chi tiết thông số server'
            );

            return response()->json([
                'success' => true,
                'data' => $data,
                'timeseries' => $timeSeriesData,
                'message' => 'Lấy danh sách monitoring server thành công',
                'type' => 'list_monitor_server_success',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách monitoring server không thành công',
                'type' => 'list_monitor_server_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $ip = $request->get('ip');

            $server = $this->serverRepository->getByIp($ip);

            if (empty($server)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server không tồn tại',
                    'type' => 'server_empty',
                ], 400);
            }

            $data = [
                'server_id' => $server->id,
                'cpu' => $request->get('cpu'),
                'ram' => $request->get('ram'),
                'disk' => $request->get('disk'),
                'services' => $request->get('services'),
                'logs' => $request->get('logs'),
                'timestamp' => $request->get('timestamp') ?? now()->toDateTimeString(),
            ];

            // Store in traditional database
            $monitorRecord = $this->monitorServerRepository->create($data);

            // Store in InfluxDB for time-series analysis
            $influxSuccess = $this->influxDBService->storeServerMetrics($data);


            return response()->json([
                'success' => true,
                'data' => [
                    'monitor_record' => $monitorRecord,
                    'influx_stored' => $influxSuccess
                ],
                'message' => 'Thêm mới thông số server thành công',
                'type' => 'create_monitor_server_success',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thêm mới thông số server không thành công',
                'type' => 'create_monitor_server_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time metrics from InfluxDB
     */
    public function getMetrics(Request $request)
    {
        try {
            $ip = $request->get('ip');
            $metricType = $request->get('metric_type'); // cpu, ram, disk
            $hours = $request->get('hours') ?? 24;

            $server = $this->serverRepository->getByIp($ip);

            if (empty($server)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server không tồn tại',
                    'type' => 'server_empty',
                ], 400);
            }

            $metrics = $this->influxDBService->getServerMetrics($server->id, $metricType, $hours);

            return response()->json([
                'success' => true,
                'data' => $metrics,
                'message' => 'Lấy metrics thành công',
                'type' => 'get_metrics_success',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy metrics không thành công',
                'type' => 'get_metrics_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service status from InfluxDB
     */
    public function getServiceStatus(Request $request)
    {
        try {
            $ip = $request->get('ip');
            $hours = $request->get('hours') ?? 24;

            $server = $this->serverRepository->getByIp($ip);

            if (empty($server)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server không tồn tại',
                    'type' => 'server_empty',
                ], 400);
            }

            $serviceStatus = $this->influxDBService->getServiceStatus($server->id, $hours);

            return response()->json([
                'success' => true,
                'data' => $serviceStatus,
                'message' => 'Lấy service status thành công',
                'type' => 'get_service_status_success',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy service status không thành công',
                'type' => 'get_service_status_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test InfluxDB connection
     */
    public function testInfluxDB()
    {
        try {
            $connectionTest = $this->influxDBService->testConnection();

            return response()->json([
                'success' => $connectionTest,
                'message' => $connectionTest 
                    ? 'Kết nối InfluxDB thành công' 
                    : 'Kết nối InfluxDB thất bại',
                'type' => $connectionTest 
                    ? 'influxdb_connection_success' 
                    : 'influxdb_connection_fail',
            ], $connectionTest ? 200 : 500);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test kết nối InfluxDB thất bại',
                'type' => 'influxdb_connection_test_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
