<?php

namespace App\Http\Controllers;

use App\Helper\Common;
use App\Jobs\StoreHtmlSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HtmlSourceController extends Controller
{
    public function saveHtml(Request $request)
    {
        $params = $request->all();
        $params = array_change_key_case($params, CASE_LOWER);
        $result = [];

        if (empty($params['url']) || empty($params['source']) || strpos($params['url'], 'youtube') !== false) {
            $result['success'] = false;

            return response()->json($result);
        }

        $data = [
            'appId' => $params['app_id'] ?? null,
            'version' => $params['version'] ?? null,
            'note' => $params['note'] ?? null,
            'deviceId' => $params['device_id'] ?? null,
            'country' => $params['country'] ?? null,
            'platform' => $params['platform'] ?? null,
            'source' => $params['source'],
            'url' => $params['url']
        ];

        StoreHtmlSource::dispatch($data)->onQueue('create_html_source');
        $result['success'] = true;

        return response()->json($result);
    }

    public function listHtmlSource(Request $request)
    {
        $input = $request->all();
        $date = $request->get('date') ?? Common::getCurrentVNTime('Y-m-d');
        $app = $request->get('app');
        $country = $request->get('country');
        $device = $request->get('device');
        $textSource = $request->get('textSource');
        $listHtmlSource = DB::table('html_sources');

        $dateFormat = date('Y-m-d');
        $apps = DB::table('html_sources')->select('app_id')->groupBy('app_id')->get();
        $countries = DB::table('html_sources')->select('country')->groupBy('country')->get();
        $devices = DB::table('html_sources')->select('device_id')->groupBy('device_id')->get();
        $query = DB::table('html_sources');

        if (!empty($country)) {
            if ($country != 'all') {
                $query = $query->where('country', $country);
            }
        }

        if (!empty($app)) {
            if ($app != 'all') {
                $query = $query->where('app_id', $app);
            }
        }

        if (!empty($date)) {
            $dateFormat = Common::getCurrentVNTime('Y-m-d');
            $query = $query->where('created_date', $date);
        }

        if (!empty($device)) {
            $query = $query->where('device_id', 'like', '%' . $device . '%');
        }
        if (!empty($textSource)) {
            $query = $query->where('source', 'like', '%' . $textSource . '%');
        }

        $countQuery = clone $query;
        $count = $countQuery->count();
        $data = $query->orderBy('id', 'desc');

        if (!empty($showInPage)) {
            if ($showInPage == 'all') {
                $dataPaginate = $data->get();
            } else {
                $dataPaginate = $data->paginate($showInPage);
            }
        } else {
            $dataPaginate = $data->paginate(20);
        }

        $row = [
            'app' => $app,
            'date' => $dateFormat,
            'textSource' => $textSource,
            'device' => $device,
            'country' => $country,
        ];

        return view('log_html_source.html_source', compact('listHtmlSource', 'row', 'apps', 'countries', 'device', 'dataPaginate', 'input', 'app', 'textSource', 'country',  'count'));
    }

    public function showHtmlSource($id)
    {
        $dataHtmlSource = DB::table('html_sources')->where('id', $id)->first();

        return response()->json($dataHtmlSource);
    }
}
