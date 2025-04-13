<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Enums\Utility;
use App\Enums\ActivityAction;
use App\Jobs\StoreAiTrainingData;
use App\Jobs\StoreHeartBeat;
use App\Jobs\StoreTrackingEvent;
use App\Jobs\StoreVideoTimeline;
use Illuminate\Http\Request;
use App\Repositories\DeviceFingerprintRepository;
use App\Repositories\TrackingEventRepository;
use App\Repositories\DomainRepository;
use UAParser\Parser;
use App\Traits\LogsActivity;
use DB;
use Exception;

class UsersTrackingController extends Controller
{
    use LogsActivity;

    protected $deviceFingerprintRepository;
    protected $trackingEventRepository;
    protected $domainRepository;
    protected $utility;

    public function __construct(
        DeviceFingerprintRepository $deviceFingerprintRepository,
        TrackingEventRepository $trackingEventRepository,
        DomainRepository $domainRepository,
        Utility $utility
    ) {
        $this->deviceFingerprintRepository = $deviceFingerprintRepository;
        $this->trackingEventRepository = $trackingEventRepository;
        $this->domainRepository = $domainRepository;
        $this->utility = $utility;
    }

    public function listTrackingEvent(Request $request)
    {
        try {
            $input = $request->all();
            $pageSize = $request->get('pageSize') ?? 10;
            $page = $request->get('page') ?? 1;
            $domain = $request->get('domain');
            $date = $request->get('date');

            if (!isset($date)) {
                $date = $this->utility->getCurrentVNTime('Y-m-d');
            }

            $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input], 'Xem danh sách users tracking');

            $query = $this->trackingEventRepository->getTrackingEventByDomain($domain, $date);
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

    public function getDetailTracking(Request $request)
    {
        try {
            $uuid = $request->get('uuid');
            $getTracking = DB::connection('mongodb')
                ->table('users_tracking')
                ->where('uuid', $uuid)
                ->orderBy('timestamp', 'asc')
                ->get();

            $userAgent = $getTracking[0]->user_agent;
            $parser = Parser::create();
            $result = $parser->parse($userAgent);

            $data = [
                'browser' => $result->ua->family,
                'os' => $result->os->family,
                'device' => $result->device->family
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy danh sách html source thành công',
                'type' => 'list_html_source_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách html source không thành công',
                'type' => 'list_html_source_fail',
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
}
