<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class CloudFlareService
{
    protected $client;
    protected $clientDNS;
    protected $apiToken;
    protected $apiTokenDNS;
    protected $apiUrl;
    protected $accountId;
    protected $wranglerPath;
    protected $logger;

    public function __construct(ApplicationLogger $logger)
    {
        $this->apiUrl = config('services.cloudflare.api_url');
        $this->apiToken = config('services.cloudflare.api_token');
        $this->apiTokenDNS = config('services.cloudflare.api_token_edit_zone_dns');
        $this->accountId = config('services.cloudflare.account_id');
        $this->wranglerPath = config('services.cloudflare.wrangler_path');
        $this->logger = $logger;
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

    /**
     * Get DNS records for a zone
     * 
     * @param string $zoneId
     * @param array $params Optional query parameters
     * @return array
     */
    public function getDnsRecords($zoneId, $params = [])
    {
        try {
            $queryString = http_build_query($params);
            $url = $this->apiUrl . "/zones/{$zoneId}/dns_records" . ($queryString ? "?{$queryString}" : "");
            $response = $this->clientDNS->get($url);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update an existing DNS record
     * 
     * @param string $zoneId
     * @param string $recordId
     * @param array $data
     * @return array
     */
    public function updateDnsRecord($zoneId, $recordId, $data)
    {
        try {
            $response = $this->clientDNS->put($this->apiUrl . "/zones/{$zoneId}/dns_records/{$recordId}", [
                'json' => $data
            ]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
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

    /**
     * Get Cloudflare Pages project details including subdomain
     * 
     * @param string $projectName
     * @return array
     */
    public function getPagesProject($projectName)
    {
        try {
            $response = $this->client->get(
                $this->apiUrl . "/accounts/{$this->accountId}/pages/projects/{$projectName}"
            );

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Set up DNS CNAME record for a domain pointing to Pages subdomain
     * 
     * @param string $domain
     * @param string $pagesSubdomain
     * @return array
     */
    public function setupDomainDNS($domain, $pagesSubdomain)
    {
        // Extract root domain for zone lookup
        $rootDomain = $this->getRootDomain($domain);
        $zoneId = $this->getZoneId($rootDomain);
        if (!$zoneId) {
            return ['error' => 'Zone ID not found for domain: ' . $rootDomain];
        }
        try {
            // For subdomains, we only need the subdomain part as the name
            $dnsName = $domain === $rootDomain ? '@' : str_replace('.' . $rootDomain, '', $domain);

            // Check for existing records
            $existingRecords = $this->getDnsRecords($zoneId, [
                'name' => $dnsName === '@' ? $rootDomain : $domain,
                // 'type' => 'CNAME'
            ]);
            $recordData = [
                'type' => 'CNAME',
                'name' => $dnsName,
                'content' => $pagesSubdomain,
                'ttl' => 1,
                'proxied' => true
            ];

            // If record exists, update it
            if (!empty($existingRecords['result'])) {
                $existingRecord = $existingRecords['result'][0];
                return $this->updateDnsRecord($zoneId, $existingRecord['id'], $recordData);
            }

            // If no existing record, create new one
            $response = $this->clientDNS->post($this->apiUrl . "/zones/{$zoneId}/dns_records", [
                'json' => $recordData
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Set cache rules for a domain
     * 
     * @param string $domain
     * @return array
     */
    public function setupCacheRules($domain)
    {
        $rootDomain = $this->getRootDomain($domain);
        $zoneId = $this->getZoneId($rootDomain);

        if (!$zoneId) {
            return [
                'success' => false,
                'error' => 'Zone ID not found for domain: ' . $rootDomain
            ];
        }

        try {
            // Create cache rule with 1 month TTL
            $response = $this->client->post($this->apiUrl . "/zones/{$zoneId}/rulesets", [
                'json' => [
                    'name' => 'Cache Rules for ' . $domain,
                    'description' => 'Cache settings for ' . $domain,
                    'kind' => 'zone',
                    'phase' => 'http_request_cache_settings',
                    'rules' => [
                        [
                            'action' => 'set_cache_settings',
                            'action_parameters' => [
                                'cache' => true,
                                'edge_ttl' => [
                                    'mode' => 'override_origin',
                                    'default' => 2592000 // 1 month in seconds
                                ],
                                'browser_ttl' => [
                                    'mode' => 'override_origin',
                                    'default' => 2592000 // 1 month in seconds
                                ]
                            ],
                            'expression' => 'true', // Apply to all requests
                            'description' => 'Cache all content for ' . $domain
                        ]
                    ]
                ]
            ]);

            return [
                'success' => true,
                'data' => json_decode($response->getBody(), true)
            ];
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Extract the root domain from a domain/subdomain
     * 
     * @param string $domain
     * @return string
     */
    private function getRootDomain($domain)
    {
        // Split the domain into parts
        $parts = explode('.', $domain);

        // If we have more than 2 parts, it's likely a subdomain
        if (count($parts) > 2) {
            // Get the last two parts for common TLDs (e.g., example.com)
            $rootDomain = implode('.', array_slice($parts, -2));

            // Get special TLDs from config
            $specialTlds = config('tlds.special', []);

            foreach ($specialTlds as $tld) {
                if (str_ends_with($domain, '.' . $tld)) {
                    $rootDomain = implode('.', array_slice($parts, -3));
                    break;
                }
            }

            return $rootDomain;
        }

        // If only 2 parts, it's already a root domain
        return $domain;
    }

    /**
     * Get all Cloudflare Pages projects (handles pagination)
     * 
     * @return array
     */
    public function getProjects()
    {
        try {
            $allProjects = [];
            $page = 1;

            do {
                // Use correct Cloudflare API pagination parameters
                $url = $this->apiUrl . "/accounts/{$this->accountId}/pages/projects";
                $params = [];

                if ($page > 1) {
                    $params['page'] = $page;
                }

                if (!empty($params)) {
                    $url .= '?' . http_build_query($params);
                }

                $this->logger->logSite("Fetching Cloudflare projects", [
                    'url' => $url,
                    'page' => $page,
                    'attempt' => 'pagination_test'
                ]);

                $response = $this->client->get($url);
                $result = json_decode($response->getBody(), true);

                $this->logger->logSite("Cloudflare API response", [
                    'page' => $page,
                    'success' => $result['success'] ?? false,
                    'result_count' => isset($result['result']) ? count($result['result']) : 0,
                    'result_info' => $result['result_info'] ?? null
                ]);

                if (!isset($result['success']) || !$result['success']) {
                    $this->logger->logSite("Cloudflare API returned error", [
                        'page' => $page,
                        'result' => $result
                    ]);
                    return [
                        'success' => false,
                        'error' => 'API returned error',
                        'details' => $result
                    ];
                }

                // Add projects from current page
                if (isset($result['result']) && is_array($result['result'])) {
                    $allProjects = array_merge($allProjects, $result['result']);
                }

                // Check if we have more pages using Cloudflare's pagination info
                $resultInfo = $result['result_info'] ?? [];
                $totalPages = $resultInfo['total_pages'] ?? 1;
                $currentPage = $resultInfo['page'] ?? $page;

                $this->logger->logSite("Pagination info", [
                    'current_page' => $currentPage,
                    'total_pages' => $totalPages,
                    'projects_so_far' => count($allProjects),
                    'per_page' => $resultInfo['per_page'] ?? 'unknown',
                    'total_count' => $resultInfo['total_count'] ?? 'unknown'
                ]);

                // If there are no more pages or we've reached the end
                if ($currentPage >= $totalPages) {
                    break;
                }

                $page++;

            } while ($page <= 100); // Safety limit to prevent infinite loops

            $this->logger->logSite("Successfully fetched all Cloudflare projects", [
                'total_projects' => count($allProjects),
                'pages_fetched' => $page
            ]);

            // Return in the same format as original API response
            return [
                'success' => true,
                'result' => $allProjects,
                'result_info' => [
                    'total_count' => count($allProjects),
                    'pages_fetched' => $page
                ]
            ];

        } catch (RequestException $e) {
            $this->logger->logSite("Cloudflare API request failed", [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);

            return $this->handleException($e);
        } catch (\Exception $e) {
            $this->logger->logSite("Unexpected error in getProjects", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Unexpected error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Remove a custom domain from a Cloudflare Pages project
     * 
     * @param string $projectName
     * @param string $domain
     * @return array
     */
    public function removePagesDomain($projectName, $domain)
    {
        try {
            $response = $this->client->delete(
                $this->apiUrl . "/accounts/{$this->accountId}/pages/projects/{$projectName}/domains/{$domain}"
            );

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Delete a Cloudflare Pages project
     * 
     * @param string $projectName
     * @return array
     */
    public function deletePagesProject($projectName)
    {
        try {
            $response = $this->client->delete(
                $this->apiUrl . "/accounts/{$this->accountId}/pages/projects/{$projectName}"
            );

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    public function deployExportDirectory($projectName, $directory = null, $domain = null, $options = [])
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
            $result['domain'] = $domain; // Add domain to result for cache purging
        } catch (\Exception $e) {
            $this->logger->logSite('Deployment failed: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
        $error = [
            'success' => false,
            'error' => 'Request failed: ' . $e->getMessage()
        ];

        if ($e->hasResponse()) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            $error['http_status'] = $statusCode;
            $error['response_body'] = $responseBody;

            // Try to decode JSON response for better error details
            $jsonResponse = json_decode($responseBody, true);
            if ($jsonResponse) {
                $error['cloudflare_errors'] = $jsonResponse['errors'] ?? null;
                $error['cloudflare_messages'] = $jsonResponse['messages'] ?? null;
                return $jsonResponse;
            }

            return $error;
        }

        return $error;
    }

    /**
     * Delete files for a specific page from Cloudflare Pages
     *
     * @param string $projectName
     * @param string $pageSlug
     * @return bool
     */
    public function deletePageFiles($projectName, $pageSlug)
    {
        try {
            $exportPath = storage_path('app/public/exports/' . $projectName . '/' . $pageSlug);

            if (File::exists($exportPath)) {
                File::deleteDirectory($exportPath);
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->logSite('Failed to delete page files: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Purge cache for a specific path
     *
     * @param string $domain
     * @param string $pageSlug
     * @return array
     */
    public function purgeCache($domain, $pageSlug)
    {
        try {
            $rootDomain = $this->getRootDomain($domain);
            $zoneId = $this->getZoneId($rootDomain);

            if (!$zoneId) {
                return [
                    'success' => false,
                    'message' => 'Zone ID not found for domain: ' . $rootDomain
                ];
            }
            // purge the domain root
            // Construct the URL pattern to purge
            $urls[] = "https://{$domain}";

            if (!empty($pageSlug)) {
                // Add URL with the pattern "{domain}/{pageSlug}"
                $urls[] = "https://{$domain}/{$pageSlug}";
                // Also add URL with trailing slash
                $urls[] = "https://{$domain}/{$pageSlug}/";


            } else {
                // If no pageSlug is provided, purge the domain root
                $urls[] = "https://{$domain}/";
            }

            $response = $this->client->post($this->apiUrl . "/zones/{$zoneId}/purge_cache", [
                'json' => [
                    'files' => $urls
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            return [
                'success' => $result['success'] ?? false,
                'message' => $result['success'] ? 'Cache purged successfully for URL: ' . implode(', ', $urls) : 'Failed to purge cache',
                'data' => $result
            ];
        } catch (RequestException $e) {
            Log::error('Cache purge failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Cache purge failed: ' . $e->getMessage(),
                'error' => $this->handleException($e)
            ];
        }
    }

    /**
     * Warm cache for specific paths on a domain
     *
     * @param string $domain
     * @param array $paths
     * @return array
     */
    public function warmCache(string $domain, array $paths = [])
    {
        $results = [];

        // Ensure domain has https:// prefix
        $domain = !str_starts_with($domain, 'http') ? "https://{$domain}" : $domain;

        foreach ($paths as $path) {
            if (empty($path)) {
                continue;
            }

            $normalizedPath = rtrim($path, '/') . '/';
            $url = rtrim($domain, '/') . '/' . ltrim($normalizedPath, '/');
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'CacheWarmerBot/1.0'
                ])
                    ->withOptions([
                        'allow_redirects' => [
                            'max' => 10,     // Increase max redirects
                            'strict' => true,
                            'referer' => true,
                            'protocols' => ['http', 'https']
                        ],
                        'timeout' => 30,    // Increase timeout to 30 seconds
                    ])
                    ->get($url);

                $results[] = [
                    'url' => $url,
                    'status' => $response->status(),
                    'success' => $response->successful()
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'url' => $url,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'success' => false
                ];
            }
        }

        return $results;
    }
}
