<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Helper\Common;
use App\Jobs\StoreHtmlSource;
use App\Repositories\HtmlSourceRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HtmlSourceController extends Controller
{
    protected $htmlSourceRepository;

    public function __construct(HtmlSourceRepository $htmlSourceRepository)
    {
        $this->htmlSourceRepository = $htmlSourceRepository;
    }

    public function saveHtml(Request $request)
    {
        try {
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

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Lưu html source thành công',
                'type' => 'store_html_source_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lưu html source không thành công',
                'type' => 'store_html_source_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listHtmlSource(Request $request)
    {
        try {
            $input = $request->all();
            $date = $request->get('date') ?? Common::getCurrentVNTime('Y-m-d');
            $app = $request->get('app');
            $country = $request->get('country');
            $device = $request->get('device');
            $textSource = $request->get('textSource');
            $listHtmlSource = $this->htmlSourceRepository->get();
            $filter = [
                'country' => $country,
                'app' => $app,
                'date' => $date,
                'device' => $device,
                'text_source' => $textSource
            ];

            $dateFormat = date('Y-m-d');
            $apps = $this->htmlSourceRepository->getAppsID();
            $countries = $this->htmlSourceRepository->getCountry();
            $dataPaginate = $this->htmlSourceRepository->getList($filter);
            $count = count($listHtmlSource);

            $row = [
                'app' => $app,
                'date' => $dateFormat,
                'textSource' => $textSource,
                'device' => $device,
                'country' => $country,
            ];

            $response = [
                'listHtmlSource' => $listHtmlSource,
                'row' => $row,
                'apps' => $apps,
                'countries' => $countries,
                'device' => $device,
                'dataPaginate' => $dataPaginate,
                'input' => $input,
                'app' => $app,
                'textSource' => $textSource,
                'country' => $country,
                'count' => $count,
            ];

            return response()->json([
                'success' => true,
                'data' => $response,
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

    public function showHtmlSource($id)
    {
        try {
            $dataHtmlSource = DB::table('html_sources')->where('id', $id)->first();

            return response()->json([
                'success' => true,
                'data' => $dataHtmlSource,
                'message' => 'Lấy chi tiết html source thành công',
                'type' => 'show_html_source_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy chi tiết html source không thành công',
                'type' => 'show_html_source_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
