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
        // Performance tracking
        $startTime = microtime(true);
        $result = ['success' => false];

        try {
            // Resolve directory path
            $deployDir = $this->resolveDeployDirectory($directory);

            // Validate directory existence
            if (!file_exists($deployDir)) {
                return [
                    'success' => false,
                    'message' => 'Directory not found: ' . $deployDir,
                    'elapsed_time' => $this->getElapsedTime($startTime)
                ];
            }

            // Execute deployment
            $deploymentCommand = $this->buildWranglerCommand($projectName, $deployDir, $options);
            $output = $this->executeWranglerCommand($deploymentCommand);

            // Process results
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

    /**
     * Resolve the directory path for deployment
     *
     * @param string|null $directory Directory path or name
     * @return string Full path to deployment directory
     */
    private function resolveDeployDirectory($directory = null)
    {
        $exportsDir = public_path('storage/exports');

        // If no directory specified, use the main exports directory
        if (empty($directory)) {
            return $exportsDir;
        }

        // Check if it's a subdirectory within exports
        $potentialSubdir = "$exportsDir/$directory";
        if (file_exists($potentialSubdir)) {
            return $potentialSubdir;
        }

        // Treat as absolute path
        return $directory;
    }

    /**
     * Build the Wrangler command for deployment
     *
     * @param string $projectName Cloudflare Pages project name
     * @param string $deployDir Directory to deploy
     * @param array $options Additional options
     * @return string Command to execute
     */
    private function buildWranglerCommand($projectName, $deployDir, $options = [])
    {
        $branch = $options['branch'] ?? 'main';
        $commitMessage = $options['commit_message'] ?? "Deployment from CMS on " . date('Y-m-d H:i:s');

        return "cd $deployDir && npx wrangler pages deploy . " .
            "--project-name=\"$projectName\" " .
            "--branch=\"$branch\" " .
            "--commit-message=\"$commitMessage\"";
    }

    /**
     * Execute the Wrangler command and return the output
     *
     * @param string $command Command to execute
     * @return string Command output
     * @throws \Exception If command execution fails
     */
    private function executeWranglerCommand($command)
    {
        // Log the command for debugging
        Log::info("Executing Wrangler command: $command");

        // Execute the command and capture output (including stderr)
        $output = shell_exec($command . " 2>&1");

        if ($output === null) {
            throw new \Exception("Failed to execute Wrangler command");
        }

        return $output;
    }

    /**
     * Process the deployment result and extract relevant information
     *
     * @param string $output Command output
     * @param string $deployDir Deployment directory
     * @param float $startTime Start time for performance tracking
     * @return array Processed result
     */
    private function processDeploymentResult($output, $deployDir, $startTime)
    {
        // Extract deployment URL if available
        $deploymentUrl = null;
        if (preg_match('/https:\/\/[a-zA-Z0-9.-]+\.pages\.dev/', $output, $matches)) {
            $deploymentUrl = $matches[0];
        }

        // Check for success indicators in output
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

    /**
     * Calculate elapsed time since start
     *
     * @param float $startTime Start time from microtime(true)
     * @return float Elapsed time in seconds
     */
    private function getElapsedTime($startTime)
    {
        return microtime(true) - $startTime;
    }
}