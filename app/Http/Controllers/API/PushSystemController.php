<?php

namespace App\Http\Controllers;

use App\Helper\Common;
use App\Helper\PushSubsAgain;
use App\Helper\PushSystem;
use App\Jobs\SavePushSystemData;
use App\Jobs\SaveRequestGetSystemSetting;
use App\Jobs\SaveUserActivePushSystem;
use Illuminate\Http\Request;
use DB;

class PushSystemController extends Controller
{
    public function saveData(Request $request)
    {
        $params = $request->all();
        $params = array_change_key_case($params, CASE_LOWER);
        $success = false;

        if (!empty($params)) {
            $success = true;
            SavePushSystemData::dispatch($params)->onQueue('save_push_system_data');
        }

        return response()->json([
            'success' => $success,
        ]);
    }

    public function getSettings(Request $request)
    {
        $kwMKDTAC = PushSubsAgain::pickKwMK(PushSubsAgain::TELCO_DTAC);
        $kwMKAIS = PushSubsAgain::pickKwMK(PushSubsAgain::TELCO_AIS);
        $shareWeb = PushSystem::getShareWebConfig();
        $linkWeb = PushSystem::pickLink($shareWeb);
        $config = PushSystem::getPushStatusAndTypeConfig();

        $arr = [
            'pushweb' => [
                'status' => $config['status'],
                'type' => $config['type'],
                'shareweb' => $shareWeb,
                'linkweb' => $linkWeb,
            ],
            'pushsms' => [
                'status' => 'off',
                'time' => 3,
                'pushnow' => 'off',
                'ais' => [$kwMKAIS],
                'dtac' => [$kwMKDTAC],
            ],

        ];

        $logData = [
            'ip' => $request->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => Common::getCurrentVNTime(),
            'created_date' => Common::getCurrentVNTime('Y-m-d'),
            'keyword_dtac' => $kwMKDTAC,
            'keyword_ais' => $kwMKAIS,
            'share_web' => $shareWeb,
            'link_web' => $linkWeb,
            'data' => $arr,
        ];

        SaveRequestGetSystemSetting::dispatch($logData)->onQueue('save_request_get_system_setting');

        return response()->json($arr);
    }

    public function listPushSystem()
    {
        $getDataCountries = DB::connection('mongodb')
            ->table('push_systems_cache')
            ->where('key', 'LIKE', 'push_systems_users_country_%')
            ->get();

        $getUserTotal = DB::connection('mongodb')
            ->table('push_systems_cache')
            ->where('key', 'push_systems_users_total')
            ->first();

        $countUser = $getUserTotal->total;
        $usersActiveCountry = DB::connection('mongodb')
            ->table('push_systems_cache')
            ->where('key', 'LIKE', 'push_systems_users_active_country_' . now()->format('Y-m-d') . '_%')
            ->get()
            ->toArray();
        $totalActive = 0;
        $activeByCountry = [];

        foreach ($usersActiveCountry as $item) {
            $totalActive += $item->total;
        }

        foreach ($getDataCountries as $country) {
            $explode = explode('_', $country->key);
            $activeByCountry[$explode[4]] = $country->total;
        }

        return view('push-system.list-push-system', compact('activeByCountry', 'countUser', 'totalActive', 'usersActiveCountry'));
    }

    public function configLinksPush()
    {
        $dataSystem = DB::table('push_systems_config')->first();

        $strLink1 = null;
        $strLink2 = null;

        if (!empty($dataSystem)) {
            $linkWeb1 = json_decode($dataSystem->link_web_1);
            $linkWeb2 = json_decode($dataSystem->link_web_2);

            $strLink1 = implode("\n", $linkWeb1);
            $strLink2 = implode("\n", $linkWeb2);
        }

        return view('push-system.create-push-system', compact('dataSystem', 'strLink1', 'strLink2'));
    }

    public function saveConfigLinksPush(Request $request)
    {
        $request->validate([
            'share_web' => 'required|numeric|min:0|max:100',
            'link_web_1' => 'required',
            'link_web_2' => 'required',
        ]);

        $params = $request->all();
        $itemArrayLink1 = preg_split('/[\r\n]+/', $params['link_web_1'], -1, PREG_SPLIT_NO_EMPTY);
        $itemArrayLink2 = preg_split('/[\r\n]+/', $params['link_web_2'], -1, PREG_SPLIT_NO_EMPTY);
        $dataSystem = DB::table('push_systems_config')->first();
        $dataPush =
            [
                'share_web' => $params['share_web'],
                'link_web_1' => json_encode($itemArrayLink1),
                'link_web_2' => json_encode($itemArrayLink2),
            ];
        if (!is_null($dataSystem)) {
            $dataPush['updated_at'] = Common::getCurrentVNTime();
            DB::table('push_systems_config')->update($dataPush);


            DB::table('systems_config_histories')->insert($dataPush);
        } else {
            $dataPush['created_at'] = Common::getCurrentVNTime();
            $dataPush['updated_at'] = Common::getCurrentVNTime();
            DB::table('push_systems_config')->insert($dataPush);
        }

        return redirect()->route('push.system.config.link.push')->with('message', 'Config updated successfully');
    }

    public function addUserActive(Request $request)
    {
        $params = $request->all();
        $params = array_change_key_case($params, CASE_LOWER);
        $success = false;

        if (!empty($params['token']) && !empty($params['country'])) {
            $success = true;
            SaveUserActivePushSystem::dispatch($params['token'], $params['country'])->onQueue('save_user_active_push_system');
        }

        return response()->json([
            'success' => $success,
        ]);
    }

    public function showConfigLinksPush()
    {

        $dataConfig = DB::table('push_systems_config')->first();
        $linkWeb1 = json_decode($dataConfig->link_web_1);
        $linkWeb2 = json_decode($dataConfig->link_web_2);

        $strLink1 = implode("\n", $linkWeb1);
        $strLink2 = implode("\n", $linkWeb2);


        return view('push-system.show-config', compact('dataConfig', 'strLink1', 'strLink2'));
    }

    public function listUserActiveAjax()
    {
        $usersActiveCountry = DB::connection('mongodb')
            ->table('push_systems_cache')
            ->where('key', 'LIKE', 'push_systems_users_active_country_' . now()->format('Y-m-d') . '_%')
            ->get()
            ->toArray();
        $totalActive = 0;

        foreach ($usersActiveCountry as $item) {
            $totalActive += $item->total;
        }

        $response = [
            'total' => $totalActive,
            'usersActiveCountry' => $usersActiveCountry,
        ];

        return response()->json($response);
    }

    public function addConfigSystemLink(Request $request)
    {
        $configDataRaw = DB::table('push_systems_config_new')->where('push_count', "!=", 0)->get();
        $configPushRow = DB::table('push_systems_config_new')->where('push_count', 0)->first();

        if (empty($configPushRow)) {
            $dataInsert = [
                'push_count' => 0,
                'status' => 'on',
                'type' => 'search',
                'created_at' => Common::getCurrentVNTime(),
            ];

            DB::table('push_systems_config_new')->insert($dataInsert);
        }

        $status = [
            'on' => 'on',
            'off' => 'off',
        ];

        $configData = [];
        foreach ($configDataRaw as $item) {
            $item->config_links = json_decode($item->config_links, true);
            $configData[$item->push_count] = $item;
        }

        return view('push-system.config-system-link', compact('configData', 'configPushRow', 'status'));
    }

    public function getCurrentPushCountAjax()
    {
        $getCurrentPushCount = DB::table('push_systems_config_new')->select(DB::raw('max(push_count) as count'))->first('count');
        $pushCount = !empty($getCurrentPushCount) ? intval($getCurrentPushCount->count) : 0;

        return response()->json([
            'pushCount' => $pushCount,
            'success' => true,
        ]);
    }

    public function saveConfigSystemLink(Request $request)
    {
        $params = $request->all();
        if (empty($params['data']) || !isset($params['share']) || empty($params['push_index'])) {
            return response()->json([
                'success' => false,
                'message' => "Invalid request",
            ]);
        }

        $configPushRowFirst = DB::table('push_systems_config_new')->where('push_count', 0)->first();
        $shareWeb = $params['share'];
        $dataInsert = [
            'status' => $configPushRowFirst->status ?? "on",
            'type' => $configPushRowFirst->type ?? "search",
            'push_count' => $params['push_index'],
            'share' => $shareWeb,
            'config_links' => json_encode($params['data']),
            'created_at' => Common::getCurrentVNTime(),
        ];
        $resultInset = DB::table('push_systems_config_new')->insert($dataInsert);

        return response()->json([
            'success' => $resultInset,
            'message' => "Data insert success",
            'params' => $params,
        ]);
    }

    public function updateConfigSystemLinkItem(Request $request, $id)
    {
        $params = $request->all();
        if (empty($params['share']) || empty($params['data'])) {
            return response()->json([
                'success' => false,
                'message' => "Invalid request",
            ]);
        }

        $resultUpdate = DB::table('push_systems_config_new')->where('id', $id)->update([
            'share' => $params['share'],
            'config_links' => json_encode($params['data']),
            'updated_at' => Common::getCurrentVNTime(),
        ]);

        return response()->json([
            'success' => $resultUpdate,
            'message' => "Update Success",
        ]);
    }

    public function saveStatusLink(Request $request)
    {
        $params = $request->all();

        if (empty($params)) {
            return response()->json([
                'success' => false,
                'message' => "Invalid request",
            ]);
        }

        $configRow = DB::table('push_systems_config_new')->where('push_count', 0)->first();
        $dataPush = [
            'status' => $params['status'],
            'type' => $params['type'],
        ];

        if (empty($configRow)) {
            $dataPush['push_count'] = 0;
            $dataPush['created_at'] = Common::getCurrentVNTime();
            $resultInset = DB::table('push_systems_config_new')->insert($dataPush);

            return response()->json([
                'success' => $resultInset,
                'message' => "Data insert success",
                'params' => $params,
            ]);
        } else {
            $dataPush['updated_at'] = Common::getCurrentVNTime();
            $resultUpdate = DB::table('push_systems_config_new')->update($dataPush);

            return response()->json([
                'success' => $resultUpdate,
                'message' => "Data update success",
                'params' => $params,
            ]);
        }
    }
}
