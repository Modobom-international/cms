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
    protected $email;

    public function __construct($email)
    {
        $this->email = $email;
        if ($this->email == 'vutuan.modobom@gmail.com') {
            $this->apiKey = config('services.godaddy_tuan.api_key');
            $this->apiSecret = config('services.godaddy_tuan.api_secret');
            $this->apiUrl = config('services.godaddy_tuan.api_url');
            $this->shopperID = config('services.godaddy_tuan.shopper_id');
        } else if ($this->email == 'tranlinh.modobom@gmail.com') {
            $this->apiKey = config('services.godaddy_linh.api_key');
            $this->apiSecret = config('services.godaddy_linh.api_secret');
            $this->apiUrl = config('services.godaddy_linh.api_url');
            $this->shopperID = config('services.godaddy_linh.shopper_id');
        } else {
            $this->apiKey = config('services.godaddy.api_key');
            $this->apiSecret = config('services.godaddy.api_secret');
            $this->apiUrl = config('services.godaddy.api_url');
            $this->shopperID = config('services.godaddy.shopper_id');
        }

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => 'sso-key ' . $this->apiKey . ':' . $this->apiSecret,
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function getDomains()
    {
        try {
            $response = $this->client->get('/v1/domains');
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    public function getDomainDetails($domain)
    {
        try {
            $response = $this->client->get("/v1/domains/{$domain}");
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    public function updateNameservers($domain)
    {
        $body = [
            "nameServers" => [
                'ben.ns.cloudflare.com',
                'jean.ns.cloudflare.com',
            ]
        ];

        $getCustomerID = $this->getCustomerID();
        $customerID = $getCustomerID['customerId'];

        try {
            $response = $this->client->put("/v2/customers/{$customerID}/domains/{$domain}/nameServers", [
                'json' => $body
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    public function getNameservers($domain)
    {
        try {
            $response = $this->client->get("/v1/domains/{$domain}/records/NS");

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    public function getCustomerID()
    {
        try {
            $response = $this->client->get("/v1/shoppers/{$this->shopperID}?includes=customerId");

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    private function handleException(RequestException $e)
    {
        if ($e->hasResponse()) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }

        return ['error' => 'Something went wrong'];
    }
}
