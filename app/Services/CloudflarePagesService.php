<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Str;

class CloudflarePagesService
{
    protected $apiToken;
    protected $accountId;
    protected $wranglerPath;

    public function __construct()
    {
        $this->apiToken = env('CLOUDFLARE_API_TOKEN');
        $this->accountId = env('CLOUDFLARE_ACCOUNT_ID');

        if (empty($this->apiToken)) {
            Log::error('Cloudflare API Token is missing. Please set CLOUDFLARE_API_TOKEN in your .env file.');
        }

        if (empty($this->accountId)) {
            Log::error('Cloudflare Account ID is missing. Please set CLOUDFLARE_ACCOUNT_ID in your .env file.');
        }
    }

    private function request($method, $endpoint, $data = [])
    {
        $url = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/pages/{$endpoint}";

        // Properly set Cloudflare authentication headers
        $response = Http::withHeaders([
                    'Authorization' => "Bearer {$this->apiToken}",
                    'Content-Type' => 'application/json',
                ])->$method($url, $data);

        return $response->json();
    }

    // 1. Create a Project
    public function createProject($projectName, $branch = 'main')
    {
        return $this->request('post', 'projects', [
            'name' => $projectName,
            'production_branch' => $branch
        ]);
    }

    // 2. Update a Project
    public function updateProject($projectName, $data)
    {
        return $this->request('patch', "projects/{$projectName}", $data);
    }

    // 3. Create a Deployment
    public function createDeployment($projectName)
    {
        return $this->request('post', "projects/{$projectName}/deployments");
    }

    // 4. Apply Domain
    public function applyDomain($projectName, $domain)
    {
        return $this->request('post', "projects/{$projectName}/domains", [
            'name' => $domain
        ]);
    }

}