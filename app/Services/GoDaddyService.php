<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GoDaddyService
{
    protected $client;
    protected $apiKey;
    protected $apiSecret;
    protected $apiUrl;
    protected $shopperID;

    protected $apiConfigs = [];

    public function __construct()
    {
        $this->apiUrl = config('services.godaddy.api_url');
        $this->apiConfigs = [
            'tuan' => [
                'api_key' => config('services.godaddy_tuan.api_key'),
                'api_secret' => config('services.godaddy_tuan.api_secret'),

                'shopper_id' => config('services.godaddy_tuan.shopper_id'),
            ],
            'linh' => [
                'api_key' => config('services.godaddy_linh.api_key'),
                'api_secret' => config('services.godaddy_linh.api_secret'),
                'api_url' => config('services.godaddy_linh.api_url'),
                'shopper_id' => config('services.godaddy_linh.shopper_id'),
            ],
        ];
    }

    protected function setClient($configKey)
    {
        $config = $this->apiConfigs[$configKey];
        $this->apiKey = $config['api_key'];
        $this->apiSecret = $config['api_secret'];
        $this->shopperID = $config['shopper_id'];

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => 'sso-key ' . $this->apiKey . ':' . $this->apiSecret,
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function getListDomain()
    {
        try {
            foreach ($this->apiConfigs as $configKey => $config) {
                $this->setClient($configKey);
                $response = $this->client->get('/v1/domains');
                $result = json_decode($response->getBody(), true);

                $listDomain = array_merge($result, $listDomain ?? []);
            }

            $data = [
                'success' => true,
                'message' => 'Lấy danh sách domain thành công',
                'data' => $listDomain,
            ];

            return $data;
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    private function handleException(RequestException $e)
    {
        if ($e->hasResponse()) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }

        return ['error' => 'Lỗi call api Godaddy rồi bro : ' . $e->getMessage()];
    }
}
