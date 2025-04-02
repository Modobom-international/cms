<?php

namespace App\Http\Controllers\API;

use App\Enums\UsersTracking;
use App\Jobs\StoreUsersTracking;
use Illuminate\Http\Request;
use App\Helper\Common;
use App\Jobs\FetchFullPage;
use App\Jobs\StoreHeatMap;
use UAParser\Parser;
use DB;

class UsersTrackingController extends Controller
{
    public function store(Request $request)
    {
        $origin = $request->header('Origin');
        $ip = request()->ip();
        if (!in_array($origin, UsersTracking::LIST_DOMAIN)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validatedData = $request->validate([
            'eventName' => 'required|string',
            'eventData' => 'required',
            'user.userAgent' => 'required|string',
            'user.platform' => 'required|string',
            'user.language' => 'required|string',
            'user.cookiesEnabled' => 'required|boolean',
            'user.screenWidth' => 'required|integer',
            'user.screenHeight' => 'required|integer',
            'user.timezone' => 'required|string',
            'timestamp' => 'required',
            'domain' => 'required',
            'uuid' => 'required',
            'path' => 'required'
        ]);

        $validatedData['user']['ip'] = $ip;
        $validatedData['timestamp'] = Common::covertDateTimeToMongoBSONDateGMT7($validatedData['timestamp']);
        StoreUsersTracking::dispatch($validatedData)->onQueue('create_users_tracking');

        if ($validatedData['eventName'] == 'mousemove' or $validatedData['eventName'] == 'click') {
            $dataHeatMap = [
                'uuid' => $validatedData['uuid'],
                'path' => $validatedData['path'],
                'domain' => $validatedData['domain'],
                'heatmapData' => [
                    'x' => $validatedData['eventData']['x'],
                    'y' => $validatedData['eventData']['y'],
                    'timestamp' => $validatedData['timestamp'],
                    'device' => $validatedData['eventData']['device'],
                    'event' => $validatedData['eventName'],
                    'height' => $validatedData['eventData']['height'],
                ],
            ];

            $dataFetch = [
                'domain' => $validatedData['domain'],
                'path' => $validatedData['path'],
                'width' => $validatedData['user']['screenWidth'],
                'height' => $validatedData['user']['screenHeight'],
                'url' => $validatedData['domain'] . $validatedData['path']
            ];

            StoreHeatMap::dispatch($dataHeatMap)->onQueue('create_heat_map');
            // FetchFullPage::dispatch($dataFetch)->onQueue('fetch_full_page');
        }

        return response()->json(['message' => 'User behavior recorded successfully.']);
    }

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

    
}
