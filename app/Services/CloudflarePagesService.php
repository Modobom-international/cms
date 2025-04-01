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

    /**
     * Deploy static files from the exports directory using Wrangler CLI
     * 
     * @param string $projectName Cloudflare Pages project name
     * @param string $directory Relative path within exports directory or full path
     * @param array $options Additional deployment options
     * @return array Deployment result
     */
    public function deployExportDirectory($projectName, $directory = null, $options = [])
    {
        // Start tracking execution time
        $startTime = microtime(true);

        // Determine the directory to deploy
        if (empty($directory)) {
            // If no specific directory provided, use the exports directory
            $deployDir = public_path('storage/exports');
        } else {
            // Check if it's a relative path within exports
            $exportsDir = public_path('storage/exports');
            if (file_exists("$exportsDir/$directory")) {
                $deployDir = "$exportsDir/$directory";
            } else {
                // Treat as absolute path
                $deployDir = $directory;
            }
        }

        // Validate directory exists
        if (!file_exists($deployDir)) {
            return [
                'success' => false,
                'message' => 'Directory not found: ' . $deployDir,
                'elapsed_time' => microtime(true) - $startTime
            ];
        }

        // Set up command options
        $branch = $options['branch'] ?? 'main';
        $commitMessage = $options['commit_message'] ?? "Deployment from CMS on " . date('Y-m-d H:i:s');

        // Build the Wrangler deploy command
        // Use npx to ensure we run wrangler without needing a specific path
        $command = "cd $deployDir && npx wrangler pages deploy . " .
            "--project-name=\"$projectName\" " .
            "--branch=\"$branch\" " .
            "--commit-message=\"$commitMessage\"";

        // Log the command for debugging
        Log::info("Executing Wrangler command: $command");

        // Execute the command
        $output = shell_exec($command . " 2>&1");

        // Check if the command executed successfully
        if ($output === null) {
            Log::error("Failed to execute Wrangler command");
            return [
                'success' => false,
                'message' => 'Failed to execute Wrangler command',
                'elapsed_time' => microtime(true) - $startTime
            ];
        }

        // Extract deployment URL from output if available
        $deploymentUrl = null;
        if (preg_match('/https:\/\/[a-zA-Z0-9.-]+\.pages\.dev/', $output, $matches)) {
            $deploymentUrl = $matches[0];
        }

        return [
            'success' => strpos($output, 'Success') !== false || strpos($output, 'Published') !== false,
            'message' => 'Deployment executed',
            'output' => $output,
            'deployment_url' => $deploymentUrl,
            'directory' => $deployDir,
            'elapsed_time' => microtime(true) - $startTime
        ];
    }
}