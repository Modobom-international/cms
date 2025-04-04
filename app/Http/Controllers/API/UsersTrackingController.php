<?php

namespace App\Http\Controllers\API;

use App\Enums\Utility;
use App\Jobs\StoreAiTrainingData;
use App\Jobs\StoreHeartBeat;
use App\Jobs\StoreTrackingEvent;
use App\Jobs\StoreVideoTimeline;
use Illuminate\Http\Request;
use App\Repositories\DeviceFingerprintRepository;
use App\Repositories\TrackingEventRepository;
use App\Repositories\DomainRepository;
use App\Http\Controllers\Controller;
use UAParser\Parser;
use DB;
use Exception;

class UsersTrackingController extends Controller
{
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

    public function viewUsersTracking(Request $request)
    {
        $domain = $request->get('domain');
        $date = $request->get('date');

        if (!isset($domain)) {
            $domain = $this->domainRepository->getFirstDomain();
        }

        if (!isset($date)) {
            $date = $this->utility->getCurrentVNTime('Y-m-d');
        }

        $query = $this->trackingEventRepository->getTrackingEventByDomain($domain, $date);

        $data = $this->utility->paginate($query->groupBy('uuid'));

        return view('users_tracking.index', compact('data'));
    }

    public function getDetailTracking(Request $request)
    {
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

        foreach ($getHeatMap as $heat) {
            $data['heat_map'][$heat->path] = $heat->heatmapData;
        }

        return response()->json($data);
    }

    public function checkDevice(Request $request): JsonResponse
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

            return response()->json(['is_excluded' => $match]);
        } catch (Exception $e) {
            return response()->json(['error' => $e, 'is_excluded' => true]);
        }
    }

    public function storeHeartbeat(Request $request): JsonResponse
    {
        $data = [
            'uuid' => $request->input('uuid'),
            'timestamp' => $request->input('timestamp'),
            'domain' => $request->input('domain'),
            'path' => $request->input('path'),
            'user_info' => $request->input('userInfo')
        ];

        StoreHeartBeat::dispatch($data)->onQueue('store_heartbeat');

        return response()->json(['status' => 'success']);
    }

    public function storeVideoTimeline(Request $request): JsonResponse
    {
        $data = [
            'uuid' => $request->input('uuid'),
            'domain' => $request->input('domain'),
            'path' => $request->input('path'),
            'start_time' => $request->input('startTime'),
            'end_time' => $request->input('endTime'),
            'total_time' => $request->input('totalTime'),
            'timeline' => $request->input('timeline'),
            'user_info' => $request->input('userInfo')
        ];

        StoreVideoTimeline::dispatch($data)->onQueue('store_video_timeline');

        return response()->json(['status' => 'success']);
    }

    public function storeAiTrainingData(Request $request): JsonResponse
    {
        $data = [
            'uuid' => $request->input('uuid'),
            'domain' => $request->input('domain'),
            'session_start' => $request->input('sessionStart'),
            'session_end' => $request->input('sessionEnd'),
            'events' => $request->input('events')
        ];

        StoreAiTrainingData::dispatch($data)->onQueue('store_ai_training_data');

        return response()->json(['status' => 'success']);
    }

    public function storeTrackingEvent(Request $request): JsonResponse
    {
        $data = [
            'uuid' => $request->input('uuid'),
            'event_name' => $request->input('eventName'),
            'event_data' => $request->input('eventData'),
            'timestamp' => $request->input('timestamp'),
            'user' => $request->input('user'),
            'domain' => $request->input('domain'),
            'path' => $request->input('path')
        ];

        StoreTrackingEvent::dispatch($data)->onQueue('store_tracking_event');

        return response()->json(['status' => 'success']);
    }
}
