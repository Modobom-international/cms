<?php

namespace App\Http\Controllers\API;

use App\Enums\UsersTracking;
use App\Jobs\StoreUsersTracking;
use Illuminate\Http\Request;
use App\Helper\Common;
use App\Jobs\StoreHeatMap;
use UAParser\Parser;
use DB;

class UsersTrackingController extends Controller
{
    public function viewUsersTracking(Request $request)
    {
        $domain = $request->get('domain');
        $date = $request->get('date');

        if (!isset($domain)) {
            $domain = UsersTracking::DEFAULT_DOMAIN;
        }

        if (!isset($date)) {
            $date = Common::getCurrentVNTime('Y-m-d');
        }

        $query = DB::connection('mongodb')
            ->table('users_tracking')
            ->where('domain', $domain)
            ->where('timestamp', '>=', Common::covertDateTimeToMongoBSONDateGMT7($date . ' 00:00:00'))
            ->where('timestamp', '<=', Common::covertDateTimeToMongoBSONDateGMT7($date . ' 23:59:59'))
            ->orderBy('timestamp', 'desc')
            ->get();

        $data = Common::paginate($query->groupBy('uuid'));

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

        $getHeatMap = DB::connection('mongodb')
            ->table('heat_map')
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

        foreach ($getTracking as $tracking) {
            $event_data = [];
            $data['heat_map'] = [];
            $data['is_internal_link'] = false;
            $data['is_lasso_button'] = false;
            $data['ip'] = $tracking->ip;

            if ($tracking->event_name == 'scroll') {
                $event_data[] = 'Cuộn xuống tọa độ x là ' . $tracking->event_data['scrollTop'] . ' và y là ' . $tracking->event_data['scrollLeft'];
                $file = 'browsershot_viewport_' . $this->data['x'] . '_' . $this->data['y'] . '_' . $this->data['width'] . '_' . $this->data['height'] . '_' . $this->data['domain'] . '_' . str_replace('/', '_', $this->data['path']) . '.png';
            }

            if ($tracking->event_name == 'beforeunload') {
                $event_data[] = 'Thời gian vào page : ' . date('Y-m-d H:i:s', $tracking->event_data['start'] / 1000);
                $event_data[] = 'Thời gian ra khỏi page : ' . date('Y-m-d H:i:s', $tracking->event_data['end'] / 1000);
                $event_data[] = 'Thời gian onpage : ' . gmdate('H:i:s', $tracking->event_data['total']);
            }

            if ($tracking->event_name == 'click') {
                $event_data[] = 'Click vào ' . $tracking->event_data['target'];
            }

            if ($tracking->event_name == 'internal_link_click') {
                $event_data[] = 'Click vào ' . $tracking->event_data['target'];
                $data['is_internal_link'] = true;
            }

            if ($tracking->event_name == 'lasso_button_click') {
                $event_data[] = 'Click vào ' . $tracking->event_data['target'];
                $data['is_lasso_button'] = true;
            }

            if ($tracking->event_name == 'keydown') {
                $event_data[] = 'Ấn nút ' . $tracking->event_data['target'] . ' với giá trị ' . $tracking->event_data['value'];
            }

            if ($tracking->event_name == 'input') {
                $event_data[] = 'Nhập ' . $tracking->event_data['target'] . ' với giá trị ' . $tracking->event_data['value'];
            }

            if ($tracking->event_name == 'mousemove') {
                $event_data[] = 'Di chuột đến vị trí x là ' . $tracking->event_data['x'] . ' và y là ' . $tracking->event_data['y'];
            }

            if ($tracking->event_name == 'resize') {
                $event_data[] = 'Thay đổi khung hình từ ' . $tracking->screen_width . 'x' . $tracking->screen_height . ' sang ' . $tracking->event_data['width'] . 'x' . $tracking->event_data['height'];
            }

            $data['activity'][$tracking->path][] = $event_data;
        }

        foreach ($getHeatMap as $heat) {
            $data['heat_map'][$heat->path] = $heat->heatmapData;
        }

        return response()->json($data);
    }

    public function getHeatMap(Request $request)
    {
        $domain = $request->get('domain');
        $path = $request->get('path');
        $date = $request->get('date');
        $event = $request->get('event');
        $data = [];
        $file = 'browsershot_fullpage_' . $domain . '_' . str_replace('/', '_', $path) . '.png';

        $query = DB::connection('mongodb')
            ->table('heat_map')
            ->where('domain', $domain)
            ->where('path', $path)
            ->where('heatmapData.timestamp', '>=', Common::covertDateTimeToMongoBSONDateGMT7($date . ' 00:00:00'))
            ->where('heatmapData.timestamp', '<=', Common::covertDateTimeToMongoBSONDateGMT7($date . ' 23:59:59'))
            ->where('heatmapData.device', 'mobile')
            ->where('heatmapData.event', $event)
            ->get();

        foreach ($query as $record) {
            $key = $record->heatmapData['x'] . '-' . $record->heatmapData['y'];
            if (array_key_exists($key, $data)) {
                $data[$key]['value'] += 1;
            } else {
                $data[$key] = [
                    'x' => $record->heatmapData['x'],
                    'y' => $record->heatmapData['y'],
                    'value' => 1,
                    'device' => $record->heatmapData['device']
                ];
            }
        }

        $response = [
            'data' => $data,
            'path_image' => '/uploads/browsershot/' . $file
        ];

        return response()->json($response);
    }

    public function getLinkForHeatMap(Request $request)
    {
        $domain = $request->get('domain');
        $data = [];

        if (!isset($domain)) {
            $domain = UsersTracking::DEFAULT_DOMAIN;
        }

        $getUrl = DB::connection('mongodb')
            ->table('heat_map')
            ->select('path')
            ->where('domain', $domain)
            ->distinct('path')
            ->get();

        foreach ($getUrl as $url) {
            $data[] = urldecode($url);
        }

        return response()->json($data);
    }

    public function checkDevice(Request $request): JsonResponse
    {
        $deviceData = [
            'user_agent' => $request->header('User-Agent'),
            'platform' => $request->input('platform'),
            'language' => $request->input('language'),
            'cookies_enabled' => $request->input('cookies_enabled'),
            'screen_width' => $request->input('screen_width'),
            'screen_height' => $request->input('screen_height'),
            'timezone' => $request->input('timezone'),
            'fingerprint' => $request->input('fingerprint'),
        ];

        $match = DeviceFingerprint::where($deviceData)->exists();
        return response()->json(['is_excluded' => $match]);
    }

    public function storeHeartbeat(Request $request): JsonResponse
    {
        Heartbeat::create([
            'uuid' => $request->input('uuid'),
            'timestamp' => $request->input('timestamp'),
            'domain' => $request->input('domain'),
            'path' => $request->input('path'),
            'user_info' => $request->input('userInfo'),
        ]);

        return response()->json(['status' => 'success']);
    }

    public function storeVideoTimeline(Request $request): JsonResponse
    {
        VideoTimeline::create([
            'uuid' => $request->input('uuid'),
            'domain' => $request->input('domain'),
            'path' => $request->input('path'),
            'start_time' => $request->input('startTime'),
            'end_time' => $request->input('endTime'),
            'total_time' => $request->input('totalTime'),
            'timeline' => $request->input('timeline'),
            'user_info' => $request->input('userInfo'),
        ]);

        return response()->json(['status' => 'success']);
    }

    public function storeAiTrainingData(Request $request): JsonResponse
    {
        AiTrainingData::create([
            'uuid' => $request->input('uuid'),
            'domain' => $request->input('domain'),
            'session_start' => $request->input('sessionStart'),
            'session_end' => $request->input('sessionEnd'),
            'events' => $request->input('events'),
        ]);

        return response()->json(['status' => 'success']);
    }

    public function storeTrackingEvent(Request $request): JsonResponse
    {
        TrackingEvent::create([
            'uuid' => $request->input('uuid'),
            'event_name' => $request->input('eventName'),
            'event_data' => $request->input('eventData'),
            'timestamp' => $request->input('timestamp'),
            'user' => $request->input('user'),
            'domain' => $request->input('domain'),
            'path' => $request->input('path'),
        ]);

        return response()->json(['status' => 'success']);
    }
}
