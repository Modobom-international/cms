<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CloudFlareService
{
    protected $client;
    protected $clientDNS;
    protected $apiToken;
    protected $apiTokenDNS;
    protected $apiUrl;
    protected $accountId;
    protected $wranglerPath;

    public function __construct()
    {
        $this->apiUrl = config('services.cloudflare.api_url');
        $this->apiToken = config('services.cloudflare.api_token');
        $this->apiTokenDNS = config('services.cloudflare.api_token_edit_zone_dns');
        $this->accountId = config('services.cloudflare.account_id');
        $this->wranglerPath = config('services.cloudflare.wrangler_path');

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

    public function createPagesProject($projectName, $branch = 'main')
    {
        try {
            $response = $this->client->post($this->apiUrl . "/accounts/{$this->accountId}/pages/projects", [
                'json' => [
                    'name' => $projectName,
                    'production_branch' => $branch
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    public function updatePagesProject($projectName, $data)
    {
        try {
            $response = $this->client->patch(
                $this->apiUrl . "/accounts/{$this->accountId}/pages/projects/{$projectName}",
                ['json' => $data]
            );

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    public function createDeployment($projectName)
    {
        try {
            $response = $this->client->post(
                $this->apiUrl . "/accounts/{$this->accountId}/pages/projects/{$projectName}/deployments"
            );

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    public function applyPagesDomain($projectName, $domain)
    {
        try {
            $response = $this->client->post(
                $this->apiUrl . "/accounts/{$this->accountId}/pages/projects/{$projectName}/domains",
                ['json' => ['name' => $domain]]
            );

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    public function deployExportDirectory($projectName, $directory = null, $options = [])
    {
        $startTime = microtime(true);
        $result = ['success' => false];

        try {
            $deployDir = $this->resolveDeployDirectory($directory);

            if (!file_exists($deployDir)) {
                return [
                    'success' => false,
                    'message' => 'Directory not found: ' . $deployDir,
                    'elapsed_time' => $this->getElapsedTime($startTime)
                ];
            }

            $deploymentCommand = $this->buildWranglerCommand($projectName, $deployDir, $options);
            $output = $this->executeWranglerCommand($deploymentCommand);

            $result = $this->processDeploymentResult($output, $deployDir, $startTime);
        } catch (\Exception $e) {
            Log::error('Deployment failed: ' . $e->getMessage());
            $result = [
                'success' => false,
                'message' => 'Deployment failed: ' . $e->getMessage(),
                'elapsed_time' => $this->getElapsedTime($startTime)
            ];
        }

        return $result;
    }

    private function resolveDeployDirectory($directory = null)
    {
        $exportsDir = public_path('storage/exports');

        if (empty($directory)) {
            return $exportsDir;
        }

        $potentialSubdir = "$exportsDir/$directory";
        if (file_exists($potentialSubdir)) {
            return $potentialSubdir;
        }

        return $directory;
    }

    private function buildWranglerCommand($projectName, $deployDir, $options = [])
    {
        $branch = $options['branch'] ?? 'main';
        $commitMessage = $options['commit_message'] ?? "Deployment from CMS on " . date('Y-m-d H:i:s');

        return "cd $deployDir && npx wrangler pages deploy . " .
            "--project-name=\"$projectName\" " .
            "--branch=\"$branch\" " .
            "--commit-message=\"$commitMessage\"";
    }

    private function executeWranglerCommand($command)
    {
        Log::info("Executing Wrangler command: $command");

        $output = shell_exec($command . " 2>&1");

        if ($output === null) {
            throw new \Exception("Failed to execute Wrangler command");
        }

        return $output;
    }

    private function processDeploymentResult($output, $deployDir, $startTime)
    {
        $deploymentUrl = null;
        if (preg_match('/https:\/\/[a-zA-Z0-9.-]+\.pages\.dev/', $output, $matches)) {
            $deploymentUrl = $matches[0];
        }

        $isSuccess = strpos($output, 'Success') !== false ||
            strpos($output, 'Published') !== false;

        return [
            'success' => $isSuccess,
            'message' => $isSuccess ? 'Deployment successful' : 'Deployment completed with issues',
            'output' => $output,
            'deployment_url' => $deploymentUrl,
            'directory' => $deployDir,
            'elapsed_time' => $this->getElapsedTime($startTime)
        ];
    }

    private function getElapsedTime($startTime)
    {
        return microtime(true) - $startTime;
    }

    private function handleException(RequestException $e)
    {
        if ($e->hasResponse()) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }

        return ['error' => 'Something went wrong'];
    }
}
