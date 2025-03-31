<?php

namespace App\Http\Controllers;

use App\Enums\EUCountry;
use App\Enums\ListAssigned;
use App\Enums\LogBehavior;
use App\Jobs\BehaviorStoreLogJob;
use Illuminate\Http\Request;
use App\Jobs\StoreLogBehaviorJob;
use App\Jobs\NotifyTelegramPartnerMyJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;
use App\Helper\LinodeStorageObject;
use App\Helper\Common;
use Auth;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use DateTimeZone;

class LogBehaviorController extends Controller
{
    public function logBehavior(Request $request)
    {
        try {
            $data = [];
            $param = $cloneParam = $request->all();
            $statusNotifyMY = false;
            $statusNotifyDenmark = false;
            $isInstall = false;
            $arrBehavior = [];
            $response = [
                'success' => false,
                'message' => 'Store log successful!'
            ];

            if (count($request->all()) == 0) {
                $response['message'] = 'Param is empty';
                return \response()->json($response);
            }

            if (empty($request->get('id'))) {
                $response['message'] = 'Id is required!';
                return \response()->json($response);
            } else {
                $data['uid'] = $request->get('id');
            }

            $info = [
                'uid' => $data['uid'],
                'message' => 'Api call : /create-log-behavior',
                'data' => json_encode($param),
                'date' => Common::covertDateTimeToMongoBSONDateGMT7(Common::getCurrentVNTime())
            ];

            BehaviorStoreLogJob::dispatch($info)->onQueue('behavior_store_log');
            foreach ($param as $key => $value) {
                if ($key == 'CONTENT_OTP' and strpos($value, 'partner.u.com.my') !== false) {
                    $export = Common::exportURLAndPinFromMSGMalaysia($value);
                    $statusNotifyMY = true;
                    if ($export['url'] != '') {
                        $export['url'] = str_replace('\/', '/', $export['url']);
                    }
                }
                if ($key == 'PUSH_DENMARK_SY_DUC') {
                    $urlDenmark = str_replace('\/', '/', $value);
                    $export = Common::exportURLAndPinFromDenmark($urlDenmark);
                    $statusNotifyDenmark = true;
                }
                if (in_array($key, LogBehavior::PARAM)) {
                    continue;
                }
                if ($key == 'INSTALL') {
                    $isInstall = true;
                }
                $arrBehavior[$key] = $value;
            }

            if ($isInstall) {
                if (empty($request->get('app'))) {
                    $response['message'] = 'App is required!';
                    return \response()->json($response);
                } else {
                    $data['app'] = $request->get('app');
                }
                if (!array_key_exists('platform', $param)) {
                    $response['message'] = 'Platform is required!';
                    return \response()->json($response);
                } else {
                    $data['platform'] = $request->get('platform');
                }
                if (!array_key_exists('country', $param)) {
                    $response['message'] = 'Country is required!';
                    return \response()->json($response);
                } else {
                    $data['country'] = $request->get('country');
                }
                if (!array_key_exists('network', $param)) {
                    $response['message'] = 'Network is required!';
                    return \response()->json($response);
                } else {
                    $data['network'] = $request->get('network');
                }
            }

            if (array_key_exists('CONTENT_OTP', $cloneParam)) {
                unset($cloneParam['CONTENT_OTP']);
            }

            if ($statusNotifyMY) {
                $encodeParam = str_replace('_', '-', json_encode($cloneParam));
                NotifyTelegramPartnerMyJob::dispatch($encodeParam, $export, 'otp', 'Malaysia - Partner')->onQueue(LinodeStorageObject::getQueueDefault());
            }

            if ($statusNotifyDenmark) {
                $encodeParam = str_replace('_', '-', json_encode($cloneParam));
                NotifyTelegramPartnerMyJob::dispatch($encodeParam, $export, 'otp', 'Denmark')->onQueue(LinodeStorageObject::getQueueDefault());
            }

            if ($request->get('date')) {
                $data['timeutc'] = $request->get('date');
            }

            $data['date'] = Common::covertDateTimeToMongoBSONDateGMT7(Common::getCurrentVNTime());
            $data['behavior'] = json_encode($arrBehavior);
            StoreLogBehaviorJob::dispatch($data, $isInstall)->onQueue('create_log_behavior');
        } catch (Exception $e) {
            $info = [
                'uid' => $data['uid'],
                'message' => 'Process API failed because with error ' . $e->getMessage(),
                'data' => '',
                'date' => Common::covertDateTimeToMongoBSONDateGMT7(Common::getCurrentVNTime())
            ];

            BehaviorStoreLogJob::dispatch($info)->onQueue('behavior_store_log');
            $response['message'] = 'Error store log behavior!';
            return \response()->json($response);
        }

        $response['success'] = true;
        return \response()->json($response);
    }

    public function viewLogBehavior(Request $request)
    {
        $date = $request->get('date');
        if (isset($date)) {
            $dateFormat = date('Y-m-d', strtotime($date));
        } else {
            $dateFormat = date('Y-m-d');
        }
        $app = $request->get('app');
        $country = $request->get('country');
        $platform = $request->get('platform');
        $network = $request->get('network');
        $install = $request->get('install');
        $showInPage = $request->get('in_page');
        $keyCacheMenuCountry = LogBehavior::CACHE_MENU . '_countries';
        $keyCacheMenuPlatform = LogBehavior::CACHE_MENU . '_platforms';
        $keyCacheMenuNetwork = LogBehavior::CACHE_MENU . '_networks';
        $keyCacheMenuApp = LogBehavior::CACHE_MENU . '_apps';
        $keyCacheKeyword = LogBehavior::CACHE_KEYWORD;
        $keyCacheDate = LogBehavior::CACHE_DATE . '_' . $dateFormat;
        $query = collect();
        $stringQuery = '';
        $countPath = 0;
        $totalPath = 0;
        if (!isset($app)) {
            $app = 'all';
        }

        if (!isset($country)) {
            $country = 'all';
        }

        if (!isset($platform)) {
            $platform = 'all';
        }

        if (!isset($network)) {
            $network = 'all';
        }

        if (!isset($install)) {
            $install = 'all';
        }

        $getCache = DB::connection('mongodb')
            ->table('log_behavior_cache')
            ->where('key', $keyCacheMenuCountry)
            ->orWhere('key', $keyCacheMenuApp)
            ->orWhere('key', $keyCacheMenuPlatform)
            ->orWhere('key', $keyCacheMenuNetwork)
            ->orWhere('key', $keyCacheKeyword)
            ->orWhere('key', $keyCacheDate)
            ->get();
        foreach ($getCache as $value) {
            if ($value->key == $keyCacheMenuCountry) {
                $countries = $value->data;
            }

            if ($value->key == $keyCacheMenuPlatform) {
                $platforms = $value->data;
            }

            if ($value->key == $keyCacheMenuNetwork) {
                $networks = $value->data;
            }

            if ($value->key == $keyCacheMenuApp) {
                $apps = collect($value->data)->sort();
            }

            if ($value->key == $keyCacheKeyword) {
                $keywords = $value->data;
            }

            if ($value->key == $keyCacheDate) {
                $stringQuery .= $value->data;
                $totalPath = $value->totalPath;
                $countPath++;
            }
        }

        $query = json_decode($stringQuery);
        $getCookieMenu = Cookie::get('menu_log_behavior');
        $getMenuInDB = DB::table('menu_filter_log_behavior')->where('cookie_id', $getCookieMenu)->first();
        $listArrayPlatform = $listDefaultPlatform = LogBehavior::PLATFORMS;
        $listArrayCountry = $listDefaultCountry = LogBehavior::COUNTRIES;
        $listArrayApp = [];
        foreach ($apps as $appItem) {
            if ($appItem != null) {
                $listArrayApp[] = $appItem;
            }
        }

        if (!empty($getMenuInDB)) {
            $menu = json_decode($getMenuInDB->menu, true);
            $listArrayPlatform = $menu['platforms'];
            $listArrayCountry = $menu['countries'];
            $listArrayApp = $menu['apps'];
        }

        if (is_array($query) and count($query) > 0 and $totalPath == $countPath) {
            $query = collect($query);
            if (isset($app)) {
                if ($app != 'all') {
                    $query = $query->filter(function ($item) use ($app) {
                        return isset($item->app) and strtolower($item->app) == strtolower($app);
                    });
                }
            }

            if (isset($country)) {
                if ($country != 'all') {
                    $query = $query->filter(function ($item) use ($country) {
                        return isset($item->country) and strtolower($item->country) == strtolower($country);
                    });
                }
            }

            if (isset($platform)) {
                if ($platform != 'all') {
                    $query = $query->filter(function ($item) use ($platform) {
                        return isset($item->platform) and $item->platform == $platform;
                    });
                }
            }

            if (isset($network)) {
                if ($network != 'all' and $network != 'other') {
                    $query = $query->filter(function ($item) use ($network) {
                        return isset($item->network) and strtolower($item->network) == strtolower($network);
                    });
                }
                if ($network == 'other') {
                    $query = $query->filter(function ($item) {
                        return isset($item->network) and ($item->network == '' or $item->network == null);
                    });
                }
            }

            if (isset($install)) {
                if ($install == 'install') {
                    $query = $query->filter(function ($item) {
                        return isset($item->behavior) and
                            strpos($item->behavior, 'INSTALL') !== false;
                    });
                }

                if ($install == 'country') {
                    $query = $query->filter(function ($item) {
                        return isset($item->behavior) and
                            strpos($item->behavior, 'INSTALL') !== false and
                            strpos($item->behavior, '"SAI_COUNTRY"') and
                            strpos($item->behavior, '"NGOAI_MANG"') === false and
                            strpos($item->behavior, 'OnePlus8Pro') === false and
                            strpos($item->behavior, 'google-Pixel 5-11') === false;
                    });
                }

                if ($install == 'network') {
                    $query = $query->filter(function ($item) {
                        return isset($item->behavior) and
                            strpos($item->behavior, 'INSTALL') !== false and
                            strpos($item->behavior, '"NGOAI_MANG"') and
                            strpos($item->behavior, '"SAI_COUNTRY"') === false and
                            strpos($item->behavior, 'OnePlus8Pro') === false and
                            strpos($item->behavior, 'google-Pixel 5-11') === false;
                    });
                }

                if ($install == 'test') {
                    $query = $query->filter(function ($item) {
                        return isset($item->behavior) and
                            strpos($item->behavior, 'INSTALL') !== false and
                            (strpos($item->behavior, 'OnePlus8Pro') or
                                strpos($item->behavior, 'google-Pixel 5-11'));
                    });
                }

                if ($install == 'sub') {
                    $query = $query->filter(function ($item) {
                        return isset($item->behavior) and
                            strpos($item->behavior, 'INSTALL') !== false and
                            strpos($item->behavior, '"SAI_COUNTRY"') === false and
                            strpos($item->behavior, '"NGOAI_MANG"') === false and
                            strpos($item->behavior, 'OnePlus8Pro') === false and
                            strpos($item->behavior, 'google-Pixel 5-11') === false;
                    });
                }

                if ($install == 'real') {
                    $query = $query->filter(function ($item) {
                        return isset($item->behavior) and
                            strpos($item->behavior, 'INSTALL') !== false and
                            strpos($item->behavior, '"SAI_COUNTRY"') === false and
                            strpos($item->behavior, 'OnePlus8Pro') === false and
                            strpos($item->behavior, 'google-Pixel 5-11') === false;
                    });
                }
            }
        } else {
            $query = DB::connection('mongodb')->table('log_behavior');

            if (!empty($app)) {
                if ($app != 'all') {
                    $query = $query->where('app', 'LIKE', $app);
                }
            }

            if (!empty($country)) {
                if ($country != 'all') {
                    $query = $query->where('country', $country);
                }
            }

            if (!empty($platform)) {
                if ($platform != 'all') {
                    $query = $query->where('platform', $platform);
                }
            }

            if (!empty($network)) {
                if ($network != 'all' and $network != 'other') {
                    $query = $query->where('network', $network);
                }
                if ($network == 'other') {
                    $query = $query->where('network', null)->orWhere('network', '');
                }
            }

            if (!empty($install)) {
                if ($install == 'install') {
                    $query = $query->where('behavior', 'LIKE', '%INSTALL%');
                }

                if ($install == 'country') {
                    $query = $query->where('behavior', 'LIKE', '%INSTALL%')
                        ->where('behavior', 'LIKE', '%SAI_COUNTRY%')
                        ->where('behavior', 'NOT LIKE', '%NGOAI_MANG%')
                        ->where('behavior', 'NOT LIKE', '%OnePlus8Pro%')
                        ->where('behavior', 'NOT LIKE', '%google-Pixel 5-11%');
                }

                if ($install == 'network') {
                    $query = $query->where('behavior', 'LIKE', '%INSTALL%')
                        ->where('behavior', 'NOT LIKE', '%SAI_COUNTRY%')
                        ->where('behavior', 'LIKE', '%NGOAI_MANG%')
                        ->where('behavior', 'NOT LIKE', '%OnePlus8Pro%')
                        ->where('behavior', 'NOT LIKE', '%google-Pixel 5-11%');
                }

                if ($install == 'test') {
                    $query = $query->where('behavior', 'LIKE', '%INSTALL%')
                        ->where(function ($query) {
                            $query->where('behavior', 'LIKE', '%OnePlus8Pro%')
                                ->orWhere('behavior', 'LIKE', '%google-Pixel 5-11%');
                        });
                }

                if ($install == 'sub') {
                    $query = $query->where('behavior', 'LIKE', '%INSTALL%')
                        ->where('behavior', 'NOT LIKE', '%SAI_COUNTRY%')
                        ->where('behavior', 'NOT LIKE', '%NGOAI_MANG%')
                        ->where('behavior', 'NOT LIKE', '%OnePlus8Pro%')
                        ->where('behavior', 'NOT LIKE', '%google-Pixel 5-11%');
                }

                if ($install == 'real') {
                    $query = $query->where('behavior', 'LIKE', '%INSTALL%')
                        ->where('behavior', 'NOT LIKE', '%SAI_COUNTRY%')
                        ->where('behavior', 'NOT LIKE', '%OnePlus8Pro%')
                        ->where('behavior', 'NOT LIKE', '%google-Pixel 5-11%');
                }
            }

            $dateEstimate1 = $dateFormat . ' 00:00:00';
            $dateEstimate2 = $dateFormat . ' 23:59:59';
            $fromQuery = Common::covertDateTimeToMongoBSONDateGMT7($dateEstimate1);
            $toQuery = Common::covertDateTimeToMongoBSONDateGMT7($dateEstimate2);
            $query = $query->where('behavior', '!=', '')->whereBetween('date', [$fromQuery, $toQuery])->orderBy('date', 'desc')->get();
        }
        $arrInstall = [];
        $arrWrongCountry = [];
        $arrWrongNetWork = [];
        $arrDeviceTest = [];
        $arrUserSub = [];
        if (Auth::user()->email == 'vytt@gmail.com' and $country == 'Malaysia') {
            $totalForThaoVy = 0;
        }

        if ($country == 'Thailand' or $country == 'Malaysia') {
            $showContent = [];
        } else {
            $showContent = 0;
        }

        if ($country == 'Thailand') {
            $queryPatten = 'SUB_OK';
        } else {
            $queryPatten = 'SUB_OK_Confirm';
        }
        
        foreach ($query as $keyQuery => $record) {
            if (!isset($record->uid)) {
                continue;
            }
            if (isset($record->behavior)) {
                if (strpos($record->behavior, '"INSTALL"')) {
                    if (!in_array($record->uid, $arrInstall)) {
                        $arrInstall[] = $record->uid;
                    }
                }

                if (
                    strpos($record->behavior, '"INSTALL"') and
                    strpos($record->behavior, '"SAI_COUNTRY"') and
                    strpos($record->behavior, '"NGOAI_MANG"') === false and
                    strpos($record->behavior, 'OnePlus8Pro') === false and
                    strpos($record->behavior, 'google-Pixel 5-11') === false
                ) {
                    if (!in_array($record->uid, $arrWrongCountry)) {
                        $arrWrongCountry[] = $record->uid;
                    }
                }

                if (
                    strpos($record->behavior, '"INSTALL"') and strpos($record->behavior, '"NGOAI_MANG"') and
                    strpos($record->behavior, '"SAI_COUNTRY"') === false and
                    strpos($record->behavior, 'OnePlus8Pro') === false and
                    strpos($record->behavior, 'google-Pixel 5-11') === false
                ) {
                    if (!in_array($record->uid, $arrWrongNetWork)) {
                        $arrWrongNetWork[] = $record->uid;
                    }
                }

                if (
                    strpos($record->behavior, '"INSTALL"') and
                    (strpos($record->behavior, 'OnePlus8Pro') or strpos($record->behavior, 'google-Pixel 5-11'))
                ) {
                    if (!in_array($record->uid, $arrDeviceTest)) {
                        $arrDeviceTest[] = $record->uid;
                    }
                }

                if (
                    strpos($record->behavior, '"INSTALL"') and
                    strpos($record->behavior, '"SAI_COUNTRY"') === false and
                    strpos($record->behavior, '"NGOAI_MANG"') === false and
                    strpos($record->behavior, 'OnePlus8Pro') === false and
                    strpos($record->behavior, 'google-Pixel 5-11') === false
                ) {
                    if (!in_array($record->uid, $arrUserSub)) {
                        $arrUserSub[] = $record->uid;
                    }
                }

                if (strpos($record->behavior, 'OnePlus8Pro') === false) {
                    $decodeBehavior = json_decode($record->behavior, true);
                    foreach ($decodeBehavior as $keyBehavior => $behavior) {
                        if (strpos($record->behavior, 'OnePlus8Pro') === false and strpos($record->behavior, 'google-Pixel 5-11') === false) {
                            if (strpos($keyBehavior, $queryPatten) !== false) {
                                if ($country == 'Thailand' or $country == 'Malaysia') {
                                    $pattern = '/[0-9][_][A-Z]/';
                                    if (preg_match($pattern, $behavior, $matches, PREG_OFFSET_CAPTURE)) {
                                        $substr = substr($behavior, $matches[0][1] + 2);
                                        $explode = explode('"}', $substr);
                                        $keyword_content = $explode[0];
                                        if (array_key_exists($keyword_content, $showContent)) {
                                            $showContent[$keyword_content] = $showContent[$keyword_content] + 1;
                                        } else {
                                            $showContent[$keyword_content] = 1;
                                        }
                                        if (isset($totalForThaoVy)) {
                                            $totalForThaoVy++;
                                        }
                                    }
                                } else {
                                    $pattern = '/[SUB_OK_Confirm]/';
                                    if (preg_match($pattern, $behavior, $matches)) {
                                        $showContent++;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (!isset($record->date_install)) {
                if (is_object($record->date)) {
                    $record->date_install = $record->date->setTimezone('UTC')->format('Y-m-d H:i:s');
                } else {
                    $explodeDate = explode('T', $record->date);
                    $explodeHours = explode('.', $explodeDate[1]);
                    $record->date_install = $explodeDate[0] . ' ' . $explodeHours[0];
                }
            } else {
                if (is_object($record->date_install)) {
                    $record->date_install = $record->date_install->setTimezone('UTC')->format('Y-m-d H:i:s');
                } else {
                    $explodeDate = explode('T', $record->date_install);
                    $explodeHours = explode('.', $explodeDate[1]);
                    $record->date_install = $explodeDate[0] . ' ' . $explodeHours[0];
                }
            }

            if (is_object($record->date)) {
                $record->date = $record->date->setTimezone('UTC')->format('Y-m-d H:i:s');
            } else {
                $explodeDate = explode('T', $record->date);
                $explodeHours = explode('.', $explodeDate[1]);
                $record->date = $explodeDate[0] . ' ' . $explodeHours[0];
            }
            $query[$keyQuery] = $record;
        }
        $textShowContent = 'Nội dung : ';

        if ($country == 'Thailand' or $country == 'Malaysia') {
            foreach ($showContent as $keyContent => $content) {
                $textShowContent .= '( ' . $keyContent . ' - ' . $content . ' ) ';
            }
        } else {
            $textShowContent .= $showContent . ' thành công';
        }

        if ($query instanceof Collection) {
            $query = $query->sortByDesc('date');
        } else {
            $query = (collect($query))->sortByDesc('date');
        }
        $statusPaginate = true;

        if (!empty($showInPage)) {
            if ($showInPage == 'all') {
                $dataPaginate = $query;
                $statusPaginate = false;
            } else {
                $dataPaginate = Common::paginate($query, $showInPage);
            }
        } else {
            $dataPaginate = Common::paginate($query, 50);
        }

        $filter = [
            'app' => $app,
            'date' => $dateFormat,
            'country' => $country,
            'network' => $network,
            'platform' => $platform,
            'install' => $install,
        ];
        $today = date('Y-m-d');
        $prevToday = date('Y-m-d', strtotime('-1 day'));

        return view('log_behavior.log_behavior', [
            'data' => $dataPaginate,
            'countries' => $countries,
            'platforms' => $platforms,
            'apps' => $apps,
            'networks' => $networks,
            'filter' => $filter,
            'statusPaginate' => $statusPaginate,
            'totalInstall' => count($arrInstall),
            'totalWrongCountry' => count($arrWrongCountry),
            'totalWrongNetWork' => count($arrWrongNetWork),
            'totalDeviceTest' => count($arrDeviceTest),
            'totalUserSub' => count($arrUserSub),
            'totalTrueInstall' => count($arrUserSub) + count($arrWrongNetWork),
            'listArrayPlatform' => $listArrayPlatform,
            'listDefaultPlatform' => $listDefaultPlatform,
            'listArrayCountry' => $listArrayCountry,
            'listDefaultCountry' => $listDefaultCountry,
            'listArrayApp' => $listArrayApp,
            'textShowContent' => $textShowContent,
            'today' => $today,
            'prevToday' => $prevToday,
            'keywords' => $keywords,
            'totalForThaoVy' => isset($totalForThaoVy) ? $totalForThaoVy : false,
        ]);
    }

    public function storeConfigFilterLogBehavior(Request $request)
    {
        $countries = $request->get('country');
        $platforms = $request->get('platform');
        $apps = $request->get('app');
        $now = date('Y-m-d H:i:s');
        $cookie_id = base64_encode($now . $this->quickRandom());
        $getCookie = DB::table('menu_filter_log_behavior')->where('cookie_id', $cookie_id)->first();
        $menu = [
            'countries' => $countries,
            'platforms' => $platforms,
            'apps' => $apps
        ];

        if (empty($getCookie)) {
            $data = [
                'cookie_id' => $cookie_id,
                'menu' => json_encode($menu)
            ];
            DB::table('menu_filter_log_behavior')->insert($data);
        } else {
            $data = [
                'menu' => json_encode($menu)
            ];
            DB::table('menu_filter_log_behavior')->where('cookie_id', $cookie_id)->update($data);
        }

        Cookie::queue(Cookie::make('menu_log_behavior', $cookie_id));
        return 'success';
    }

    public function resetConfigFilterLogBehavior()
    {
        $cookieMenu = Cookie::forget('menu_log_behavior');
        return back()->withCookie($cookieMenu);
    }

    public function getDataChartLogBehavior(Request $request)
    {
        $data = [];
        $from = $request->get('from');
        $to = $request->get('to');
        if (strtotime($from) > strtotime($to)) {
            $from = date('Y-m-d', strtotime($to));
        }
        $app = $request->get('app');
        $country = $request->get('country');
        $platform = $request->get('platform');
        $keyword = $request->get('keyword');
        $fromFormat = date('Y-m-d', strtotime($from));
        $toFormat = date('Y-m-d', strtotime($to));
        $period = CarbonPeriod::create($fromFormat, $toFormat);
        $arrKeyDate = [];
        $arrDate = [];
        $arrTotal = [];
        $arrSend = [];
        foreach ($period as $datePeriod) {
            $dateFormat = $datePeriod->format('Y-m-d');
            $keyCacheDate = LogBehavior::CACHE_DATE . '_' . $dateFormat;
            $arrKeyDate[] = $keyCacheDate;
            $arrDate[$dateFormat] = '';
        }
        $getListByDate = DB::connection('mongodb')
            ->table('log_behavior_cache')
            ->whereIn('key', $arrKeyDate)
            ->get();
        foreach ($getListByDate as $record) {
            $explode = explode('_', $record->key);
            $arrDate[$explode[2]] .= $record->data;
        }
        foreach ($arrDate as $date => $totalData) {
            $data['labels'][] = $date;
            $install = 0;
            $send = 0;

            if (!is_array($totalData)) {
                $query = json_decode($totalData);
                if (isset($network)) {
                    if ($network != 'all' and $network != 'other') {
                        $query = $query->filter(function ($item) use ($network) {
                            return isset($item->network) and strtolower($item->network) == strtolower($network);
                        });
                    }
                    if ($network == 'other') {
                        $query = $query->filter(function ($item) {
                            return isset($item->network) and ($item->network == '' or $item->network == null);
                        });
                    }
                }

                if (isset($country)) {
                    if ($country != 'all') {
                        $query = $query->filter(function ($item) use ($country) {
                            return isset($item->country) and strtolower($item->country) == strtolower($country);
                        });
                    }
                }

                if (isset($platform)) {
                    if ($platform != 'all') {
                        $query = $query->filter(function ($item) use ($platform) {
                            return isset($item->platform) and $item->platform == $platform;
                        });
                    }
                }

                if (isset($app)) {
                    if ($app != 'all') {
                        $query = $query->filter(function ($item) use ($app) {
                            return isset($item->app) and strtolower($item->app) == strtolower($app);
                        });
                    }
                }

                if (isset($keyword)) {
                    if ($keyword != 'all') {
                        $query = $query->filter(function ($item) use ($keyword) {
                            return isset($item->behavior) and strpos($item->behavior, $keyword) !== false;
                        });
                    }
                }

                foreach ($query as $value) {
                    if (strpos($value->behavior, 'INSTALL') !== false) {
                        $install++;
                    }
                    if ($keyword != 'all') {
                        if ($value->behavior != null) {
                            $behavior = json_decode($value->behavior, true);
                            foreach ($behavior as $behaviorKey => $behaviorValue) {
                                if (strpos(strtolower($behaviorKey), strtolower('SUB_OK_')) !== false and $behaviorValue == $keyword) {
                                    $send += 1;
                                }
                            }
                        }
                    } else {
                        $countDefault = substr_count(strtolower($value->behavior), strtolower('SUB_OK_KwDefault'));
                        $countApp = substr_count(strtolower($value->behavior), strtolower('SUB_OK_KwOutApp'));
                        $countApi = substr_count(strtolower($value->behavior), strtolower('SUB_OK_KwApi'));
                        $countConfirm = substr_count(strtolower($value->behavior), strtolower('SUB_OK_Confirm'));
                        if (strtolower($country) == 'thailand') {
                            if ($countDefault > 0) {
                                $send += $countDefault;
                            }
                            if ($countApp > 0) {
                                $send += $countApp;
                            }
                            if ($countApi > 0) {
                                $send += $countApi;
                            }
                        } elseif (in_array(strtolower($country), EUCountry::EU_COUNTRIES)) {
                            if ($countConfirm > 0) {
                                $send += $countConfirm;
                            }
                        } else {
                            if ($countDefault > 0) {
                                $send += $countDefault;
                            }
                            if ($countApp > 0) {
                                $send += $countApp;
                            }
                            if ($countApi > 0) {
                                $send += $countApi;
                            }
                            if ($countConfirm > 0) {
                                $send += $countConfirm;
                            }
                        }
                    }
                }
            } else {
                $query = DB::connection('mongodb');

                if (strtotime($dateFormat) == strtotime(date('Y-m-d'))) {
                    $query = $query->table('log_behavior');
                } else {
                    $query = $query->table('log_behavior_history');
                }

                if (!empty($app)) {
                    if ($app != 'all') {
                        $query = $query->where('app', 'LIKE', $app);
                    }
                }

                if (!empty($country)) {
                    if ($country != 'all') {
                        $query = $query->where('country', $country);
                    }
                }

                if (!empty($platform)) {
                    if ($platform != 'all') {
                        $query = $query->where('platform', $platform);
                    }
                }

                if (!empty($network)) {
                    if ($network != 'all' and $network != 'other') {
                        $query = $query->where('network', $network);
                    }
                    if ($network == 'other') {
                        $query = $query->where('network', null)->orWhere('network', '');
                    }
                }

                if (!empty($install)) {
                    if ($install == 'install') {
                        $query = $query->where('behavior', 'LIKE', '%INSTALL%');
                    }
                    if ($install == 'country') {
                        $query = $query->where('behavior', 'LIKE', '%INSTALL%')
                            ->where('behavior', 'LIKE', '%SAI_COUNTRY%')
                            ->where('behavior', 'NOT LIKE', '%NGOAI_MANG%')
                            ->where('behavior', 'NOT LIKE', '%OnePlus8Pro%')
                            ->where('behavior', 'NOT LIKE', '%google-Pixel 5-11%');
                    }
                    if ($install == 'network') {
                        $query = $query->where('behavior', 'LIKE', '%INSTALL%')
                            ->where('behavior', 'NOT LIKE', '%SAI_COUNTRY%')
                            ->where('behavior', 'LIKE', '%NGOAI_MANG%')
                            ->where('behavior', 'NOT LIKE', '%OnePlus8Pro%')
                            ->where('behavior', 'NOT LIKE', '%google-Pixel 5-11%');
                    }
                    if ($install == 'test') {
                        $query = $query->where('behavior', 'LIKE', '%INSTALL%')
                            ->where(function ($query) {
                                $query->where('behavior', 'LIKE', '%OnePlus8Pro%')
                                    ->orWhere('behavior', 'LIKE', '%google-Pixel 5-11%');
                            });
                    }
                    if ($install == 'sub') {
                        $query = $query->where('behavior', 'LIKE', '%INSTALL%')
                            ->where('behavior', 'NOT LIKE', '%SAI_COUNTRY%')
                            ->where('behavior', 'NOT LIKE', '%NGOAI_MANG%')
                            ->where('behavior', 'NOT LIKE', '%OnePlus8Pro%')
                            ->where('behavior', 'NOT LIKE', '%google-Pixel 5-11%');
                    }
                    if ($install == 'real') {
                        $query = $query->where('behavior', 'LIKE', '%INSTALL%')
                            ->where('behavior', 'NOT LIKE', '%SAI_COUNTRY%')
                            ->where('behavior', 'NOT LIKE', '%OnePlus8Pro%')
                            ->where('behavior', 'NOT LIKE', '%google-Pixel 5-11%');
                    }
                }

                $dateEstimate1 = $dateFormat . ' 00:00:00';
                $dateEstimate2 = $dateFormat . ' 23:59:59';
                $fromQuery = Common::covertDateTimeToMongoBSONDateGMT7($dateEstimate1);
                $toQuery = Common::covertDateTimeToMongoBSONDateGMT7($dateEstimate2);
                $query = $query->where('behavior', '!=', '')->whereBetween('date', [$fromQuery, $toQuery])->orderBy('date', 'desc')->get();
                foreach ($query as $value) {
                    if (strpos($value->behavior, 'INSTALL') !== false) {
                        $install++;
                    }
                    if ($keyword != 'all') {
                        if ($value->behavior != null) {
                            $behavior = json_decode($value->behavior, true);
                            foreach ($behavior as $behaviorKey => $behaviorValue) {
                                if (strpos(strtolower($behaviorKey), strtolower('SUB_OK_')) !== false and $behaviorValue == $keyword) {
                                    $send += 1;
                                }
                            }
                        }
                    } else {
                        $countDefault = substr_count(strtolower($value->behavior), strtolower('SUB_OK_KwDefault'));
                        $countApp = substr_count(strtolower($value->behavior), strtolower('SUB_OK_KwOutApp'));
                        $countApi = substr_count(strtolower($value->behavior), strtolower('SUB_OK_KwApi'));
                        $countConfirm = substr_count(strtolower($value->behavior), strtolower('SUB_OK_Confirm'));
                        if (strtolower($country) == 'thailand') {
                            if ($countDefault > 0) {
                                $send += $countDefault;
                            }
                            if ($countApp > 0) {
                                $send += $countApp;
                            }
                            if ($countApi > 0) {
                                $send += $countApi;
                            }
                        } elseif (in_array(strtolower($country), EUCountry::EU_COUNTRIES)) {
                            if ($countConfirm > 0) {
                                $send += $countConfirm;
                            }
                        } else {
                            if ($countDefault > 0) {
                                $send += $countDefault;
                            }
                            if ($countApp > 0) {
                                $send += $countApp;
                            }
                            if ($countApi > 0) {
                                $send += $countApi;
                            }
                            if ($countConfirm > 0) {
                                $send += $countConfirm;
                            }
                        }
                    }
                }
            }
            $arrTotal[] = $install;
            $arrSend[] = $send;
        }

        $sumTotal = array_sum($arrTotal);
        $sumSend = array_sum($arrSend);
        $data['datasets'][] = (object)[
            'label' => 'Tổng lượt cài',
            'data' => $arrTotal,
            'borderColor' => 'rgb(0, 0, 0)',
            'pointStyle' => 'circle',
            'pointRadius' => 10,
            'pointHoverRadius' => 15,
        ];

        $data['datasets'][] = (object)[
            'label' => 'Tổng lượt gửi',
            'data' => $arrSend,
            'borderColor' => 'rgb(0, 255, 0)',
            'pointStyle' => 'circle',
            'pointRadius' => 10,
            'pointHoverRadius' => 15,
        ];

        $data['sum'] = [
            'total' => $sumTotal,
            'send' => $sumSend
        ];

        return \response()->json($data);
    }

    public function quickRandom($length = 16)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }

    public function compareDate(Request $request)
    {
        $datePrevious = $request->get('datePrevious');
        $dateSelected = $request->get('dateSelected');
        $app = $request->get('app');
        $country = $request->get('country');
        $platform = $request->get('platform');
        $network = $request->get('network');
        $install = $request->get('install');
        $time = $request->get('time');
        $arrInstall = [];
        $arrWrongCountry = [];
        $arrWrongNetWork = [];
        $arrDeviceTest = [];
        $arrUserSub = [];
        $dateFormatPrevious = date('Y-m-d', strtotime($datePrevious));
        $dateFormatSelected = date('Y-m-d', strtotime($dateSelected));
        $keyCacheDate = LogBehavior::CACHE_DATE . '_' . $dateFormatPrevious;
        $query = collect();
        $stringQuery = '';
        if (strtotime($dateFormatPrevious) == strtotime(date('Y-m-d'))) {
            $query = DB::connection('mongodb')->table('log_behavior');
            if (!empty($app)) {
                if ($app != 'all') {
                    $query = $query->where('app', 'LIKE', $app);
                }
            }

            if (!empty($country)) {
                if ($country != 'all') {
                    $query = $query->where('country', $country);
                }
            }

            if (!empty($platform)) {
                if ($platform != 'all') {
                    $query = $query->where('platform', $platform);
                }
            }

            if (!empty($network)) {
                if ($network != 'all' and $network != 'other') {
                    $query = $query->where('network', $network);
                }
                if ($network == 'other') {
                    $query = $query->where('network', null)->orWhere('network', '');
                }
            }
            if (!empty($install)) {
                if ($install == 'install') {
                    $query = $query->where('behavior', 'LIKE', '%INSTALL%');
                }

                if ($install == 'country') {
                    $query = $query->where('behavior', 'LIKE', '%INSTALL%')
                        ->where('behavior', 'LIKE', '%SAI_COUNTRY%')
                        ->where('behavior', 'NOT LIKE', '%NGOAI_MANG%')
                        ->where('behavior', 'NOT LIKE', '%OnePlus8Pro%')
                        ->where('behavior', 'NOT LIKE', '%google-Pixel 5-11%');
                }

                if ($install == 'network') {
                    $query = $query->where('behavior', 'LIKE', '%INSTALL%')
                        ->where('behavior', 'NOT LIKE', '%SAI_COUNTRY%')
                        ->where('behavior', 'LIKE', '%NGOAI_MANG%')
                        ->where('behavior', 'NOT LIKE', '%OnePlus8Pro%')
                        ->where('behavior', 'NOT LIKE', '%google-Pixel 5-11%');
                }

                if ($install == 'test') {
                    $query = $query->where('behavior', 'LIKE', '%INSTALL%')
                        ->where(function ($query) {
                            $query->where('behavior', 'LIKE', '%OnePlus8Pro%')
                                ->orWhere('behavior', 'LIKE', '%google-Pixel 5-11%');
                        });
                }

                if ($install == 'sub') {
                    $query = $query->where('behavior', 'LIKE', '%INSTALL%')
                        ->where('behavior', 'NOT LIKE', '%SAI_COUNTRY%')
                        ->where('behavior', 'NOT LIKE', '%NGOAI_MANG%')
                        ->where('behavior', 'NOT LIKE', '%OnePlus8Pro%')
                        ->where('behavior', 'NOT LIKE', '%google-Pixel 5-11%');
                }

                if ($install == 'real') {
                    $query = $query->where('behavior', 'LIKE', '%INSTALL%')
                        ->where('behavior', 'NOT LIKE', '%SAI_COUNTRY%')
                        ->where('behavior', 'NOT LIKE', '%OnePlus8Pro%')
                        ->where('behavior', 'NOT LIKE', '%google-Pixel 5-11%');
                }
            }

            $dateEstimate1 = $dateFormatPrevious . ' 00:00:00';
            $dateEstimate2 = $dateFormatPrevious . ' ' . $time;
            $fromQuery = Common::covertDateTimeToMongoBSONDateGMT7($dateEstimate1);
            $toQuery = Common::covertDateTimeToMongoBSONDateGMT7($dateEstimate2);
            $query = $query->where('behavior', '!=', '')->whereBetween('date', [$fromQuery, $toQuery])->orderBy('date', 'desc')->get();
        } else {
            $getCache = DB::connection('mongodb')
                ->table('log_behavior_cache')
                ->where('key', $keyCacheDate)
                ->get();
            if (count($getCache) > 0) {
                foreach ($getCache as $cache) {
                    $stringQuery .= $cache->data;
                }
                $query = collect(json_decode($stringQuery));
            }
            if (isset($app)) {
                if ($app != 'all') {
                    $query = $query->filter(function ($item) use ($app) {
                        return isset($item->app) and strtolower($item->app) == strtolower($app);
                    });
                }
            }
            if (isset($country)) {
                if ($country != 'all') {
                    $query = $query->filter(function ($item) use ($country) {
                        return isset($item->country) and strtolower($item->country) == strtolower($country);
                    });
                }
            }
            if (isset($platform)) {
                if ($platform != 'all') {
                    $query = $query->filter(function ($item) use ($platform) {
                        return isset($item->platform) and $item->platform == $platform;
                    });
                }
            }
            if (isset($network)) {
                if ($network != 'all' and $network != 'other') {
                    $query = $query->filter(function ($item) use ($network) {
                        return isset($item->network) and strtolower($item->network) == strtolower($network);
                    });
                }
                if ($network == 'other') {
                    $query = $query->filter(function ($item) {
                        return isset($item->network) and ($item->network == '' or $item->network == null);
                    });
                }
            }
            if (isset($install)) {
                if ($install == 'install') {
                    $query = $query->filter(function ($item) {
                        return isset($item->behavior) and
                            strpos($item->behavior, 'INSTALL') !== false;
                    });
                }

                if ($install == 'country') {
                    $query = $query->filter(function ($item) {
                        return isset($item->behavior) and
                            strpos($item->behavior, 'INSTALL') !== false and
                            strpos($record->behavior, '"SAI_COUNTRY"') and
                            strpos($record->behavior, '"NGOAI_MANG"') === false and
                            strpos($record->behavior, 'OnePlus8Pro') === false and
                            strpos($record->behavior, 'google-Pixel 5-11') === false;
                    });
                }

                if ($install == 'network') {
                    $query = $query->filter(function ($item) {
                        return isset($item->behavior) and
                            strpos($item->behavior, 'INSTALL') !== false and
                            strpos($record->behavior, '"NGOAI_MANG"') and
                            strpos($record->behavior, '"SAI_COUNTRY"') === false and
                            strpos($record->behavior, 'OnePlus8Pro') === false and
                            strpos($record->behavior, 'google-Pixel 5-11') === false;
                    });
                }

                if ($install == 'test') {
                    $query = $query->filter(function ($item) {
                        return isset($item->behavior) and
                            strpos($item->behavior, 'INSTALL') !== false and
                            (strpos($record->behavior, 'OnePlus8Pro') or
                                strpos($record->behavior, 'google-Pixel 5-11'));
                    });
                }

                if ($install == 'sub') {
                    $query = $query->filter(function ($item) {
                        return isset($item->behavior) and
                            strpos($item->behavior, 'INSTALL') !== false and
                            strpos($record->behavior, '"SAI_COUNTRY"') === false and
                            strpos($record->behavior, '"NGOAI_MANG"') === false and
                            strpos($record->behavior, 'OnePlus8Pro') === false and
                            strpos($record->behavior, 'google-Pixel 5-11') === false;
                    });
                }

                if ($install == 'real') {
                    $query = $query->filter(function ($item) {
                        return isset($item->behavior) and
                            strpos($item->behavior, 'INSTALL') !== false and
                            strpos($item->behavior, '"SAI_COUNTRY"') === false and
                            strpos($item->behavior, 'OnePlus8Pro') === false and
                            strpos($item->behavior, 'google-Pixel 5-11') === false;
                    });
                }
            }
            if (strtotime($dateFormatSelected) == strtotime(date('Y-m-d'))) {
                $query = $query->filter(function ($item) use ($time) {
                    $explodeDate = explode('.', $item->date);
                    $explodeTime = explode('T', $explodeDate[0]);
                    $dateTime = $explodeTime[0] . ' ' . $explodeTime[1];
                    $datePare = $explodeTime[0] . ' ' . $time;
                    $dateStart = $explodeTime[0] . ' 00:00:00';
                    return strtotime($dateStart) <= strtotime($dateTime) and strtotime($dateTime) <= strtotime($datePare);
                });
            }
        }
        foreach ($query as $keyQuery => $record) {
            if (isset($record->behavior)) {
                if (strpos($record->behavior, '"INSTALL"')) {
                    if (!in_array($record->uid, $arrInstall)) {
                        $arrInstall[] = $record->uid;
                    }
                }
                if (strpos($record->behavior, '"INSTALL"') and strpos($record->behavior, '"SAI_COUNTRY"') and strpos($record->behavior, '"NGOAI_MANG"') === false and strpos($record->behavior, 'OnePlus8Pro') === false) {
                    if (!in_array($record->uid, $arrWrongCountry)) {
                        $arrWrongCountry[] = $record->uid;
                    }
                }
                if (strpos($record->behavior, '"INSTALL"') and strpos($record->behavior, '"NGOAI_MANG"') and strpos($record->behavior, '"SAI_COUNTRY"') === false and strpos($record->behavior, 'OnePlus8Pro') === false) {
                    if (!in_array($record->uid, $arrWrongNetWork)) {
                        $arrWrongNetWork[] = $record->uid;
                    }
                }
                if (strpos($record->behavior, '"INSTALL"') and strpos($record->behavior, 'OnePlus8Pro')) {
                    if (!in_array($record->uid, $arrDeviceTest)) {
                        $arrDeviceTest[] = $record->uid;
                    }
                }
                if (
                    strpos($record->behavior, '"INSTALL"') and
                    strpos($record->behavior, '"SAI_COUNTRY"') === false and
                    strpos($record->behavior, '"NGOAI_MANG"') === false and
                    strpos($record->behavior, 'OnePlus-OnePlus8Pro-11') === false and
                    strpos($record->behavior, 'OnePlus8Pro') === false
                ) {
                    if (!in_array($record->uid, $arrUserSub)) {
                        $arrUserSub[] = $record->uid;
                    }
                }
            }
            $query[$keyQuery] = $record;
        }

        $response = [
            'total-id-previous' => count($query),
            'total-install-previous' => count($arrInstall),
            'wrong-country-previous' => count($arrWrongCountry),
            'wrong-network-previous' => count($arrWrongNetWork),
            'device-test-previous' => count($arrDeviceTest),
            'user-sub-previous' => count($arrUserSub),
            'true-install-previous' => count($arrUserSub) + count($arrWrongNetWork)
        ];

        return \response()->json($response);
    }
    public function getActivityUid(Request $request)
    {
        $uid = $request->get('uid');
        $getActivity = DB::connection('mongodb')
            ->table('log_behavior_activity')
            ->where('uid', $uid)
            ->get();

        foreach ($getActivity as $activity) {
            $phpDate = $activity->date->toDateTime();
            $phpDate->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
            $activity->date = $phpDate->format('Y-m-d H:i:s');
        }

        return \response()->json($getActivity);
    }
}
