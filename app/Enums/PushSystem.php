<?php

namespace App\Enums;

use Illuminate\Support\Facades\Redis;

class PushSystem
{
    const KW_MK = 'MK';
    const KW_F2U = 'F2U';

    const TELCO_DTAC = 'DTAC';
    const TELCO_AIS = 'AIS';

    const LIMIT_PUSH_SUBS = 200;

    const KEY_CACHE_PUSH_PREFIX = 'push_subs_again_index_';
    const PROVIDERS = [
        self::KW_MK,
        self::KW_F2U,
    ];

    const KEYWORDS_PUSH = [

        self::KW_MK => [
            self::TELCO_DTAC => [
                //DTAC
                //EMAT_4541572
                //GO_4541370
                //IVF_4541293
                //IVG_4541293
                //RSTH_4541544
                //THRS_4541544

                [
                    'keyword' => 'EMAT',
                    'shortcode' => '4541572',
                ],
                [
                    'keyword' => 'GO',
                    'shortcode' => '4541370',
                ],
                [
                    'keyword' => 'IVF',
                    'shortcode' => '4541293',
                ],
                [
                    'keyword' => 'IVG',
                    'shortcode' => '4541293',
                ],
                [
                    'keyword' => 'RSTH',
                    'shortcode' => '4541544',
                ],
                [
                    'keyword' => 'THRS',
                    'shortcode' => '4541544',
                ],


            ],
            self::TELCO_AIS => [
                ///AIS
                //IVF_4541293
                //WICAT_4541763
                //NARA_4541352
                //GAMES_4541545
                //TCL_4541571
                //DSC_4541770
                [
                    'keyword' => 'IVF',
                    'shortcode' => '4541293',
                ],
                [
                    'keyword' => 'WICAT',
                    'shortcode' => '4541763',
                ],
                [
                    'keyword' => 'NARA',
                    'shortcode' => '4541352',
                ],
                [
                    'keyword' => 'GAMES',
                    'shortcode' => '4541545',
                ],
                [
                    'keyword' => 'TCL',
                    'shortcode' => '4541571',
                ],
                [
                    'keyword' => 'DSC',
                    'shortcode' => '4541770',
                ],

            ],
        ],

        self::KW_F2U => [
            self::TELCO_DTAC => [
                ///DTAC
                //F1_4761619
                //J1_4761469
                //R1_4761602
                //A1_4761590
                //R1_4761602
                //J1_4761469
                [
                    'keyword' => 'X1',
                    'shortcode' => '4761608',
                ],
                [
                    'keyword' => 'L1',
                    'shortcode' => '4761625',
                ],
                [
                    'keyword' => 'M1',
                    'shortcode' => '4761626',
                ],
                [
                    'keyword' => 'Z1',
                    'shortcode' => '4761643',
                ],
                [
                    'keyword' => 'F1',
                    'shortcode' => '4761619',
                ],
                [
                    'keyword' => 'J1',
                    'shortcode' => '4761469',
                ],
                [
                    'keyword' => 'R1',
                    'shortcode' => '4761602',
                ],
                [
                    'keyword' => 'A1',
                    'shortcode' => '4761590',
                ],
                [
                    'keyword' => 'R1',
                    'shortcode' => '4761602',
                ],

            ],
            self::TELCO_AIS => [
                /// AIS
                //G1_4761620
                //Z1_4761613
                //T1_4761604
                //Q1_4761601
                //M1_4761597
                [
                    'keyword' => 'X1',
                    'shortcode' => '4761608',
                ],
                [
                    'keyword' => 'L1',
                    'shortcode' => '4761625',
                ],
                [
                    'keyword' => 'M1',
                    'shortcode' => '4761626',
                ],
                [
                    'keyword' => 'Z1',
                    'shortcode' => '4761643',
                ],
                [
                    'keyword' => 'G1',
                    'shortcode' => '4761620',
                ],
                [
                    'keyword' => 'Z1',
                    'shortcode' => '4761613',
                ],
                [
                    'keyword' => 'T1',
                    'shortcode' => '4761604',
                ],
                [
                    'keyword' => 'Q1',
                    'shortcode' => '4761601',
                ],
                [
                    'keyword' => 'M1',
                    'shortcode' => '4761597',
                ],

            ],
        ],

    ];

    const SHAREWEB_DEFAULT = 60; //vni 60%, apkafe 40%
    const CACHE_TIME_SECONDS = 120; //2 minutes
    const USER_ACTIVE_PREVIOUS = 4000;
    const PUSH_INDEX_TTL_MINUTES = 120; //2 hours

    public static function pickLink($shareWeb = 0)
    {
        if ($shareWeb < 0 || $shareWeb > 100) {
            $shareWeb = 0;
        }

        if ($shareWeb == 0) {
            return self::pickLink1();
        }

        if ($shareWeb == 100) {
            return self::pickLink2();
        }

        $share = rand(1, 99);
        if ($share < $shareWeb) {
            return self::pickLink2();
        }

        return self::pickLink1();
    }

    public static function pickLinkApkafe()
    {
        $keyCache = self::getKeyCacheSettingPushLink('apkafe');

        $links = \Cache::store('redis')->remember($keyCache, self::CACHE_TIME_SECONDS, function () {

            return Common::file2Array(storage_path('onesignal/config_link_push/apkafe.txt'));
        });

        if (empty($links)) {
            \Cache::store('redis')->forget($keyCache);

            return null;
        }

        return $links[array_rand($links)];
    }

    public static function pickLinkVni()
    {
        $keyCache = self::getKeyCacheSettingPushLink('vni');

        $links = \Cache::store('redis')->remember($keyCache, self::CACHE_TIME_SECONDS, function () {

            return Common::file2Array(storage_path('onesignal/config_link_push/vni.txt'));
        });

        if (empty($links)) {
            \Cache::store('redis')->forget($keyCache);

            return null;
        }

        return $links[array_rand($links)];
    }

    public static function getKeyCacheSettingPushLink($linkType)
    {
        return 'push_system_link_' . strtolower(trim($linkType));
    }

    public static function pickLink1()
    {
        $pushIndex = self::getCurrentPushIndex();

        $keyCache = self::getKeyCacheSettingPushLink('apkafe');
        $links = \Cache::store('redis')->remember($keyCache, self::CACHE_TIME_SECONDS, function () use ($pushIndex) {

            $data = \DB::table('push_systems_config_new')->where('push_count', $pushIndex)->first();
            $configLinks = !empty($data->config_links) ? json_decode($data->config_links, true) : [];

            $links1 = [];
            if (!empty($configLinks['link_push_1'])) {
                $links1 = $configLinks['link_push_1'];
            }

            if (empty($links1)) {
                return array_filter(Common::file2Array(storage_path('onesignal/config_link_push/apkafe.txt')));
            }

            return array_filter($links1);
        });

        if (empty($links)) {
            \Cache::store('redis')->forget($keyCache);

            return null;
        }

        return $links[array_rand($links)];
    }

    public static function pickLink2()
    {
        $pushIndex = self::getCurrentPushIndex();

        $keyCache = self::getKeyCacheSettingPushLink('vni');

        $links = \Cache::store('redis')->remember($keyCache, self::CACHE_TIME_SECONDS, function () use ($pushIndex) {

            $data = \DB::table('push_systems_config_new')->where('push_count', $pushIndex)->first();
            $configLinks = !empty($data->config_links) ? json_decode($data->config_links, true) : [];

            $links2 = [];
            if (!empty($configLinks['link_push_2'])) {
                $links2 = $configLinks['link_push_2'];
            }

            if (empty($links2)) {
                return array_filter(Common::file2Array(storage_path('onesignal/config_link_push/vni.txt')));
            }

            return array_filter($links2);
        });

        if (empty($links)) {
            \Cache::store('redis')->forget($keyCache);

            return null;
        }

        return $links[array_rand($links)];
    }

    public static function getShareWebConfig()
    {
        $keyCache = self::getKeyCacheSettingPushLink('shareweb');
        $pushIndex = self::getCurrentPushIndex();

        $shareWeb = \Cache::store('redis')->remember($keyCache, self::CACHE_TIME_SECONDS, function () use ($pushIndex) {

            $data = \DB::table('push_systems_config_new')->where('push_count', $pushIndex)->first('share');
            if (empty($data)) {
                return self::SHAREWEB_DEFAULT;
            }

            return intval($data->share);
        });

        return intval($shareWeb);
    }

    public static function getCurrentPushIndex()
    {
        $info = \Cache::store('redis')->get(self::getKeyCacheCurrentPushIndex());

        if (empty($info)) {
            self::setCurrentPushIndex(1);

            return 1;
        }

        $activate = $info['activated_at'] ?? Common::getCurrentVNTime();
        $currentPushIndex = $info['index'] ?? 0;

        $start = new Carbon($activate);
        $end = new Carbon(Common::getCurrentVNTime());

        $diffMinutes = $end->diffInMinutes($start);

        if ($diffMinutes >= self::PUSH_INDEX_TTL_MINUTES) {
            $nextIndex = $currentPushIndex + 1;
            if ($nextIndex > self::getMaxPushSystemIndex()) {
                $nextIndex = 1;
            }

            self::setCurrentPushIndex($nextIndex);

            return $nextIndex;
        }

        return $currentPushIndex;
    }

    public static function setCurrentPushIndex($pushIndex)
    {
        $data = [
            'index' => $pushIndex,
            'activated_at' => Common::getCurrentVNTime(),
        ];

        \Cache::store('redis')->set(self::getKeyCacheCurrentPushIndex(), $data);
    }

    public static function getMaxPushSystemIndex()
    {
        $keyCache = self::getKeyCacheMaxPushSystemIndex();

        $maxPushIndex = \Cache::store('redis')->remember($keyCache, self::CACHE_TIME_SECONDS, function () {
            $maxPushIndex = \DB::table('push_systems_config_new')->select(\DB::raw('max(push_count) as push_index'))->first('push_index');

            return !empty($maxPushIndex) ? intval($maxPushIndex->push_index) : 0;
        });

        return intval($maxPushIndex);
    }

    private static function getKeyCacheCurrentPushIndex()
    {
        return 'push_system_link_push_index';
    }

    private static function getKeyCacheMaxPushSystemIndex()
    {
        return 'push_system_link_max_push_index';
    }

    public static function getPushStatusAndTypeConfig()
    {
        $keyCache = self::getKeyCacheSettingPushLink('status_type');

        $configStatusType = \Cache::store('redis')->remember($keyCache, self::CACHE_TIME_SECONDS, function () {

            $data = \DB::table('push_systems_config_new')->where('push_count', 0)->first();
            if (empty($data)) {
                return [
                    'status' => 'on',
                    'type' => 'search',
                ];
            }

            return [
                'status' => $data->status,
                'type' => $data->type,
            ];
        });

        return $configStatusType;
    }


    public static function pushSubsAgain($country = 'thailand', $limit = self::LIMIT_PUSH_SUBS, $version = Onesignal::VERSION_2)
    {
        $timeDiff = 6 * 60;

        $playersId = \DB::table('onesignal_push_subs_again')
            ->whereRaw('(ROUND(TIMESTAMPDIFF(minute, created_at, now())) >= ?)', [$timeDiff])
            ->limit($limit)
            ->pluck('player_id')->toArray();

        if (count($playersId) === 0) {
            dump('[Push subs again] Nothing to do!');

            return false;
        }

        $kwMKDTAC = self::pickKwMK(self::TELCO_DTAC);
        $kwMKAIS = self::pickKwMK(self::TELCO_AIS);

        $msgMK = [
            'type' => 'sms',
            'dtac' => [
                $kwMKDTAC,
            ],
            'ais' => [
                $kwMKAIS,
            ],
        ];

        $responseMK = Onesignal::sendNotificationByIds($country, json_encode($msgMK), $playersId, $version);
        dump('Pushed onesignal subs again - MK, count: ' . count($playersId));
        dump($responseMK);
        dump('DTAC - MK');
        dump($kwMKDTAC);
        dump('AIS - MK');
        dump($kwMKDTAC);

        $recordsMK = [];

        foreach ($playersId as $playerId) {
            $recordsMK[] = [
                'player_id' => $playerId,
                'country' => $country,
                'provider' => self::KW_MK,
                'kw_dtac' => json_encode($kwMKDTAC),
                'kw_ais' => json_encode($kwMKAIS),
                'created_at' => Common::getCurrentVNTime(),
            ];
        }

        \DB::transaction(function () use ($playersId, $recordsMK) {
            $resultMK = \DB::table('onesignal_push_subs_again_history')->insert($recordsMK);
            $resultDelete = \DB::table('onesignal_push_subs_again')->whereIn('player_id', $playersId)->delete();

            dump('- Inserted history MK: result ' . $resultMK);
            dump('=> Deleted players id: result ' . $resultDelete);
        });

        dump('Done!');
    }


    public static function getCurrentKw($provider, $telco)
    {
        if (empty(self::KEYWORDS_PUSH[strtoupper(trim($provider))][strtoupper(trim($telco))])) {
            return null;
        }

        $provider = strtolower($provider);
        $telco = strtolower($telco);

        $key = self::getKeyRedisKwIndex($provider, $telco);
        $index = Redis::get($key);
        if (empty($index)) {
            $index = 0;
        }

        if (empty(self::KEYWORDS_PUSH[strtoupper(trim($provider))][strtoupper(trim($telco))][$index])) {
            $index = 0;
        }

        return [
            'index' => $index,
            'keyword_info' => self::KEYWORDS_PUSH[strtoupper(trim($provider))][strtoupper(trim($telco))][$index],
        ];
    }

    public static function setKwIndex($provider, $telco, $index)
    {
        if (empty(self::KEYWORDS_PUSH[strtoupper(trim($provider))][strtoupper(trim($telco))])) {
            return null;
        }

        $provider = strtolower($provider);
        $telco = strtolower($telco);

        $key = self::getKeyRedisKwIndex($provider, $telco);
        $index = intval($index);
        Redis::set($key, $index);
    }

    public static function getNextKeywordF2U() {}

    public static function pickKwMK($telco)
    {
        return self::pickKw(self::KW_MK, $telco);
    }

    public static function pickKwF2U($telco)
    {
        return self::pickKw(self::KW_F2U, $telco);
    }

    private static function pickKw($provider, $telco)
    {
        $currentKw = self::getCurrentKw($provider, $telco);

        $kw = $currentKw['keyword_info'];
        if ($telco == self::TELCO_AIS) {
            $kw['shortcode'] = '+' . $kw['shortcode'];
        }

        $nextIndex = ($currentKw['index'] + 1);
        self::setKwIndex($provider, $telco, $nextIndex);

        return $kw;
    }


    private static function getKeyRedisKwIndex($provider, $telco)
    {
        return sprintf(self::KEY_CACHE_PUSH_PREFIX . '%s_%s', $provider, $telco);
    }
}
