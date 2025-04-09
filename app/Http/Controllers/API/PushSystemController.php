<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\StorePushSystem;
use App\Enums\PushSystem;
use App\Enums\Utility;
use App\Http\Requests\PushSystemConfigRequest;
use App\Http\Requests\PushSystemRequest;
use App\Http\Requests\PushSystemUserActiveRequest;
use App\Http\Requests\PushSystemSettingRequest;
use App\Http\Requests\UpdatePushSystemConfigRequest;
use App\Jobs\StorePushSystemConfig;
use App\Jobs\StorePushSystemUserActive;
use App\Jobs\StorePushSystemSetting;
use App\Jobs\UpdatePushSystemConfig;
use App\Models\PushSystemConfig;
use App\Repositories\PushSystemCacheRepository;
use App\Repositories\PushSystemConfigRepository;
use App\Repositories\PushSystemUserActiveRepository;
use Exception;

class PushSystemController extends Controller
{
    protected $pushSystem;
    protected $utility;
    protected $pushSystemCacheRepository;
    protected $pushSystemUserActiveRepository;
    protected $pushSystemConfigRepository;

    public function __construct(
        PushSystem $pushSystem,
        Utility $utility,
        PushSystemCacheRepository $pushSystemCacheRepository,
        PushSystemUserActiveRepository $pushSystemUserActiveRepository,
        PushSystemConfigRepository $pushSystemConfigRepository,
    ) {
        $this->utility = $utility;
        $this->pushSystem = $pushSystem;
        $this->pushSystemCacheRepository = $pushSystemCacheRepository;
        $this->pushSystemUserActiveRepository = $pushSystemUserActiveRepository;
        $this->pushSystemConfigRepository = $pushSystemConfigRepository;
    }

    public function storePushSystem(PushSystemRequest $request)
    {
        try {
            $params = $request->all();
            $params = array_change_key_case($params, CASE_LOWER);

            if (!empty($params)) {
                StorePushSystem::dispatch($params)->onQueue('store_push_system');
            }

            return response()->json([
                'success' => true,
                'data' => $params,
                'message' => 'Lưu push system thành công',
            ], 202);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lưu dữ liệu không thành công'
            ], 500);
        }
    }

    public function storePushSystemSetting(PushSystemSettingRequest $request)
    {
        try {
            $kwMKDTAC = PushSystem::pickKwMK(PushSystem::TELCO_DTAC);
            $kwMKAIS = PushSystem::pickKwMK(PushSystem::TELCO_AIS);
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

            $data = [
                'ip' => $request->getClientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'created_date' => $this->utility->getCurrentVNTime('Y-m-d'),
                'keyword_dtac' => $kwMKDTAC,
                'keyword_ais' => $kwMKAIS,
                'share_web' => $shareWeb,
                'link_web' => $linkWeb,
                'data' => $arr,
            ];

            StorePushSystemSetting::dispatch($data)->onQueue('store_push_system_setting');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lưu push system setting thành công',
            ], 202);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lưu push system setting không thành công',
            ], 500);
        }
    }

    public function listPushSystem()
    {
        try {
            $getDataCountries = $this->pushSystemCacheRepository->getByKeyLike('push_systems_users_country_%');
            $getUserTotal = $this->pushSystemCacheRepository->getFirstByKey('push_systems_users_total');
            $countUser = $getUserTotal->total;
            $usersActiveCountry = $this->pushSystemCacheRepository->getByKeyLike('push_systems_users_active_country_' . now()->format('Y-m-d') . '_%')->toArray();
            $totalActive = 0;
            $activeByCountry = [];

            foreach ($usersActiveCountry as $item) {
                $totalActive += $item->total;
            }

            foreach ($getDataCountries as $country) {
                $explode = explode('_', $country->key);
                $activeByCountry[$explode[4]] = $country->total;
            }

            $data = [
                'active_by_country' => $activeByCountry,
                'count_user' => $countUser,
                'total_active' => $totalActive,
                'users_active_country' => $usersActiveCountry,
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy danh sách push system thành công',
                'type' => 'list_push_system_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách push system không thành công',
                'type' => 'list_push_system_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storePushSystemUserActive(PushSystemUserActiveRequest $request)
    {
        try {
            $params = $request->validated();
            $params = array_change_key_case($params, CASE_LOWER);
            $data = [
                'token' => $params['token'] ?? null,
                'country' => $params['country'] ?? null,
                'activated_at' => $this->utility->getCurrentVNTime('Y-m-d H:i:s'),
                'activated_date' => $this->utility->getCurrentVNTime('Y-m-d'),
            ];

            StorePushSystemUserActive::dispatch($data)->onQueue('store_push_system_user_active');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lưu push system user active thành công',
            ], 202);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lưu push system user active không thành công',
            ], 500);
        }
    }

    public function listPushSystemUserActive()
    {
        try {
            $usersActiveCountry = $this->pushSystemCacheRepository->getByKeyLike('push_systems_users_active_country_' . now()->format('Y-m-d') . '_%')->toArray();
            $totalActive = 0;

            foreach ($usersActiveCountry as $item) {
                $totalActive += $item->total;
            }

            $data = [
                'total' => $totalActive,
                'users_active_country' => $usersActiveCountry,
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy danh sách push system user active thành công',
                'type' => 'list_push_system_user_active_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách push system user active không thành công',
                'type' => 'list_push_system_user_active_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storePushSystemConfigByAdmin()
    {
        try {
            $configDataRaw = $this->pushSystemConfigRepository->getConfigDataRaw();
            $configPushRow = $this->pushSystemConfigRepository->getConfigPushRow();

            if (empty($configPushRow)) {
                $dataInsert = [
                    'push_count' => 0,
                    'status' => 'on',
                    'type' => 'search'
                ];

                StorePushSystemConfig::dispatch($dataInsert)->onQueue('store_push_system_config');
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

            $data = [
                'config_data' => $configData,
                'config_push_row' => $configPushRow,
                'status' => $status
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Tạo push system config thành công',
                'type' => 'store_push_system_config_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tạo push system config không thành công',
                'type' => 'store_push_system_config_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCountCurrentPush()
    {
        try {
            $getCurrentPushCount = $this->pushSystemConfigRepository->getCurrentPushCount();
            $pushCount = !empty($getCurrentPushCount) ? intval($getCurrentPushCount->count) : 0;

            $data = [
                'push_count' => $pushCount
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lấy count current push thành công',
                'type' => 'get_push_system_config_count_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy count current push không thành công',
                'type' => 'get_push_system_config_count_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storePushSystemConfig(PushSystemConfigRequest $request)
    {
        try {
            $params = $request->validated();
            $configPushRowFirst = $this->pushSystemConfigRepository->getConfigPushRow();
            $shareWeb = $params['share'];
            $data = [
                'status' => $configPushRowFirst->status ?? "on",
                'type' => $configPushRowFirst->type ?? "search",
                'push_count' => $params['push_index'],
                'share' => $shareWeb,
                'config_links' => json_encode($params['data'])
            ];

            StorePushSystemConfig::dispatch($data)->onQueue('store_push_system_config');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lưu push system config thành công',
            ], 202);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lưu push system config không thành công',
            ], 500);
        }
    }

    public function updatePushSystemConfig(UpdatePushSystemConfigRequest $request, $id)
    {
        try {
            $params = $request->validated();
            $data = [
                'share' => $params['share'],
                'config_links' => json_encode($params['data']),
                'updated_at' => Common::getCurrentVNTime(),
            ];
            $this->pushSystemConfigRepository->updateById($id, $data);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Cập nhật push system config thành công',
                'type' => 'update_push_system_config_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật push system config không thành công',
                'type' => 'update_push_system_config_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeStatusLink(PushSystemConfigRequest $request)
    {
        try {
            $params = $request->validated();
            $configRow = $this->pushSystemConfigRepository->getConfigPushRow();
            $data = [
                'status' => $params['status'],
                'type' => $params['type'],
            ];

            if (empty($configRow)) {
                $data['push_count'] = 0;
                StorePushSystemConfig::dispatch($data)->onQueue('store_push_system_config');
            } else {
                UpdatePushSystemConfig::dispatch($data, $configRow->id)->onQueue('store_push_system_config');
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lưu status link thành công',
            ], 202);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lưu status link không thành công',
            ], 500);
        }
    }
}
