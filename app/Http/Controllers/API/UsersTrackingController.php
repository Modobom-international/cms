<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Enums\Utility;
use App\Enums\ActivityAction;
use App\Jobs\StoreAiTrainingData;
use App\Jobs\StoreHeartBeat;
use App\Jobs\StoreTrackingEvent;
use App\Jobs\StoreVideoTimeline;
use App\Repositories\DeviceFingerprintRepository;
use App\Repositories\TrackingEventRepository;
use App\Repositories\DomainRepository;
use App\Repositories\HeartBeatRepository;
use App\Traits\LogsActivity;
use Exception;
use Carbon\Carbon;

class UsersTrackingController extends Controller
{
    use LogsActivity;

    protected $deviceFingerprintRepository;
    protected $trackingEventRepository;
    protected $domainRepository;
    protected $heartBeatRepository;
    protected $utility;

    public function __construct(
        DeviceFingerprintRepository $deviceFingerprintRepository,
        TrackingEventRepository $trackingEventRepository,
        DomainRepository $domainRepository,
        HeartBeatRepository $heartBeatRepository,
        Utility $utility
    ) {
        $this->deviceFingerprintRepository = $deviceFingerprintRepository;
        $this->trackingEventRepository = $trackingEventRepository;
        $this->domainRepository = $domainRepository;
        $this->heartBeatRepository = $heartBeatRepository;
        $this->utility = $utility;
    }

    public function listTrackingEvent(Request $request)
    {
        try {
            $input = $request->all();
            $pageSize = $request->get('pageSize') ?? 10;
            $page = $request->get('page') ?? 1;
            $domain = $request->get('domain');
            $path = $request->get('path');
            $date = $request->get('date');

            if (!isset($date)) {
                $date = $this->utility->getCurrentVNTime('Y-m-d');
            }

            if (!isset($path)) {
                $path = 'all';
            }

            $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input], 'Xem danh sách users tracking');

            $query = $this->trackingEventRepository->getTrackingEventByDomain($domain, $date, $path);
            $data = $this->utility->paginate($query->groupBy('uuid'), $pageSize, $page);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy danh sách tracking event thành công',
                'type' => 'list_tracking_event_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách tracking event không thành công',
                'type' => 'list_tracking_event_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkDevice(Request $request)
    {
        try {
            $deviceData = [
                'user_agent' => $request->header('User-Agent'),
                'platform' => $request->input('platform'),
                'language' => $request->input('language'),
                'cookies_enabled' => $request->input('cookies_enabled'),
                'screen_width' => $request->input('screen_width'),
                'screen_height' => $request->input('screen_height'),
                'timezone' => $request->input('timezone'),
                'fingerprint' => $request->input('fingerprint')
            ];

            $match = $this->deviceFingerprintRepository->getDeviceFingerprint($deviceData);

            return response()->json([
                'success' => true,
                'is_excluded' => $match,
                'message' => 'Kiểm tra thiết bị thành công',
            ], 202);
        } catch (Exception $e) {
            return response()->json([
                'success' => true,
                'is_excluded' => true,
                'message' => 'Kiểm tra thiết bị không thành công',
            ], 202);
        }
    }

    public function storeHeartbeat(Request $request)
    {
        try {
            $userInfo = $request->input('userInfo');

            $data = [
                'uuid' => $request->input('uuid'),
                'timestamp' => $request->input('timestamp'),
                'domain' => $request->input('domain'),
                'path' => $request->input('path'),
                'user_info' => $userInfo
            ];

            StoreHeartBeat::dispatch($data)->onQueue('store_heartbeat');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lưu heart beat thành công',
            ], 202);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lưu heart beat không thành công',
            ], 500);
        }
    }

    public function storeVideoTimeline(Request $request)
    {
        try {
            $userInfo = $request->input('userInfo');

            $data = [
                'uuid' => $request->input('uuid'),
                'domain' => $request->input('domain'),
                'path' => $request->input('path'),
                'start_time' => $request->input('startTime'),
                'end_time' => $request->input('endTime'),
                'total_time' => $request->input('totalTime'),
                'timeline' => $request->input('timeline'),
                'user_info' => $userInfo
            ];

            StoreVideoTimeline::dispatch($data)->onQueue('store_video_timeline');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lưu video timeline thành công',
            ], 202);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lưu video timeline không thành công',
            ], 500);
        }
    }

    public function storeAiTrainingData(Request $request)
    {
        try {
            $data = [
                'uuid' => $request->input('uuid'),
                'domain' => $request->input('domain'),
                'session_start' => $request->input('sessionStart'),
                'session_end' => $request->input('sessionEnd'),
                'events' => $request->input('events')
            ];

            StoreAiTrainingData::dispatch($data)->onQueue('store_ai_training_data');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lưu ai training data thành công',
            ], 202);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lưu ai training data không thành công',
            ], 500);
        }
    }

    public function storeTrackingEvent(Request $request)
    {
        try {
            $user = $request->input('user');

            $data = [
                'uuid' => $request->input('uuid'),
                'event_name' => $request->input('eventName'),
                'event_data' => $request->input('eventData'),
                'timestamp' => $request->input('timestamp'),
                'user' => $user,
                'domain' => $request->input('domain'),
                'path' => $request->input('path')
            ];

            StoreTrackingEvent::dispatch($data)->onQueue('store_tracking_event');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lưu tracking event thành công',
            ], 202);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lưu tracking event không thành công',
            ], 500);
        }
    }

    public function getCurrentUsersActive(Request $request)
    {
        try {
            $domain = $request->query('domain');
            $path = $request->query('path', 'all');

            if (!$domain) {
                return response()->json([
                    'success' => false,
                    'message' => 'Domain không được trống',
                    'type' => 'get_current_user_active_fail',
                ], 400);
            }

            $timeBreak = 1;
            $diffTime = Carbon::now()->subMinutes($timeBreak);
            $cacheKey = "active_users_{$domain}_{$path}";

            $count = Cache::remember($cacheKey, 10, function () use ($domain, $path, $diffTime) {
                $activeUsers = $this->heartBeatRepository->getCurrentUsersActive($domain, $path, $diffTime);

                return $activeUsers;
            });

            return response()->json([
                'success' => true,
                'data' => $count,
                'message' => 'Lấy số lượng current users active thành công',
                'type' => 'get_current_user_active_success',
            ], 200);
        } catch (Exception $e) {
            \Log::error('Error in getCurrentUsersActive', [
                'domain' => $domain,
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lấy số lượng current users active không thành công',
                'type' => 'get_current_user_active_fail',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
