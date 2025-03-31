<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CloudFlareService
{
    protected $client;
    protected $clientDNS;
    protected $apiToken;
    protected $apiTokenDNS;
    protected $apiUrl;
    protected $accountId;

    public function __construct()
    {
        $this->apiUrl = config('services.cloudflare.api_url');
        $this->apiToken = config('services.cloudflare.api_token_edit_zone');
        $this->apiTokenDNS = config('services.cloudflare.api_token_edit_zone_dns');
        $this->accountId = config('services.cloudflare.account_id');

        $this->client = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->clientDNS = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiTokenDNS,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function getZoneId($domain)
    {
        $response = $this->client->get($this->apiUrl . "/zones?name={$domain}");
        $result = json_decode($response->getBody(), true);
        $zoneID = $result['result'][0]['id'] ?? null;

        return $zoneID;
    }

    public function updateDnsARecord($domain, $ip)
    {
        $zoneId = $this->getZoneId($domain);
        if (!$zoneId) {
            return ['error' => 'Không tìm thấy Zone ID'];
        }

        try {
            $body = [
                'type' => 'A',
                'name' => $domain,
                'content' => $ip,
                'ttl' => 1,
                'proxied' => true
            ];

            $response = $this->clientDNS->post($this->apiUrl . "/zones/{$zoneId}/dns_records", [
                'json' => $body
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    public function addDomain($domain)
    {
        $body = [
            'name' => $domain,
            'account' => ['id' => $this->accountId],
            'jump_start' => true
        ];

        try {
            $response = $this->client->post($this->apiUrl . '/zones', ['json' => $body]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    public function deleteDomain($domain)
    {
        $zoneId = $this->getZoneId($domain);
        if (!$zoneId) {
            return ['error' => 'Không tìm thấy Zone ID'];
        }

        try {
            $response = $this->client->delete($this->apiUrl . "/zones/{$zoneId}");

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
