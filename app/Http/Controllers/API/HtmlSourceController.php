<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Helper\Utility;
use App\Http\Requests\HtmlSourceRequest;
use App\Jobs\StoreHtmlSource;
use App\Repositories\HtmlSourceRepository;
use Illuminate\Http\Request;
use Exception;

class HtmlSourceController extends Controller
{
    protected $htmlSourceRepository;
    protected $utility;

    public function __construct(HtmlSourceRepository $htmlSourceRepository, Utility $utility)
    {
        $this->htmlSourceRepository = $htmlSourceRepository;
        $this->utility = $utility;
    }

    public function saveHtml(HtmlSourceRequest $request)
    {
        try {
            $params = $request->validated();
            $params = array_change_key_case($params, CASE_LOWER);

            $data = [
                'app_id' => $params['app_id'] ?? null,
                'version' => $params['version'] ?? null,
                'note' => $params['note'] ?? null,
                'device_id' => $params['device_id'] ?? null,
                'country' => $params['country'] ?? null,
                'platform' => $params['platform'] ?? null,
                'source' => $params['source'],
                'url' => $params['url'],
                'created_date' => $this->utility->getCurrentVNTime('Y-m-d'),
            ];

            StoreHtmlSource::dispatch($data)->onQueue('store_html_source');

            return response()->json([
                'success' => true,
                'data' => $params,
                'message' => 'Lưu html source thành công',
            ], 202);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lưu html source không thành công'
            ], 500);
        }
    }

    public function listHtmlSource(Request $request)
    {
        try {
            $input = $request->all();
            $date = $request->get('date') ?? $this->utility->getCurrentVNTime('Y-m-d');
            $app = $request->get('app');
            $country = $request->get('country');
            $device = $request->get('device');
            $textSource = $request->get('textSource');
            $listHtmlSource = $this->htmlSourceRepository->all();
            $filter = [
                'country' => $country,
                'app' => $app,
                'date' => $date,
                'device' => $device,
                'text_source' => $textSource
            ];

            $dateFormat = date('Y-m-d');
            $apps = $this->htmlSourceRepository->getApps();
            $countries = $this->htmlSourceRepository->getCountries();
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
            $dataHtmlSource = $this->htmlSourceRepository->getByID($id);

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
