<?php

namespace App\Repositories;

use App\Models\DnsRecord;

class DnsRecordRepository extends BaseRepository
{
    public function model()
    {
        return DnsRecord::class;
    }

    /**
     * Get DNS records for a specific domain
     * 
     * @param string $domain
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByDomain($domain)
    {
        return $this->model->where('domain', $domain)->get();
    }

    /**
     * Get DNS records by CloudFlare zone ID
     * 
     * @param string $zoneId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByZoneId($zoneId)
    {
        return $this->model->where('zone_id', $zoneId)->get();
    }

    /**
     * Find DNS record by CloudFlare ID
     * 
     * @param string $cloudflareId
     * @return \App\Models\DnsRecord|null
     */
    public function findByCloudflareId($cloudflareId)
    {
        return $this->model->where('cloudflare_id', $cloudflareId)->first();
    }

    /**
     * Update or create DNS record from CloudFlare data
     * 
     * @param array $recordData
     * @param string|null $domain Override domain (if provided by calling code)
     * @return \App\Models\DnsRecord
     */
    public function syncFromCloudflare($recordData, $domain = null)
    {
        $resolvedDomain = $domain ?? $recordData['resolved_domain'] ?? $this->extractDomainFromName($recordData['name']);

        return $this->model->updateOrCreate(
            ['cloudflare_id' => $recordData['id']],
            [
                'zone_id' => $recordData['zone_id'],
                'domain' => $resolvedDomain,
                'type' => $recordData['type'],
                'name' => $recordData['name'],
                'content' => $recordData['content'],
                'ttl' => $recordData['ttl'],
                'proxied' => $recordData['proxied'] ?? false,
                'meta' => $recordData['meta'] ?? null,
                'comment' => $recordData['comment'] ?? null,
                'tags' => $recordData['tags'] ?? null,
                'cloudflare_created_on' => isset($recordData['created_on']) ? \Carbon\Carbon::parse($recordData['created_on']) : null,
                'cloudflare_modified_on' => isset($recordData['modified_on']) ? \Carbon\Carbon::parse($recordData['modified_on']) : null,
            ]
        );
    }

    /**
     * Delete DNS records that are not in the provided CloudFlare IDs
     * 
     * @param string $domain
     * @param array $cloudflareIds
     * @return int Number of deleted records
     */
    public function deleteObsoleteRecords($domain, $cloudflareIds)
    {
        return $this->model->where('domain', $domain)
            ->whereNotIn('cloudflare_id', $cloudflareIds)
            ->delete();
    }

    /**
     * Get DNS records by type for a domain
     * 
     * @param string $domain
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByDomainAndType($domain, $type)
    {
        return $this->model->where('domain', $domain)
            ->where('type', $type)
            ->get();
    }

    /**
     * Get proxied records for a domain
     * 
     * @param string $domain
     * @param bool $proxied
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProxiedRecords($domain, $proxied = true)
    {
        return $this->model->where('domain', $domain)
            ->where('proxied', $proxied)
            ->get();
    }

    /**
     * Extract domain from DNS record name
     * This handles cases where name might be a subdomain
     * 
     * @param string $name
     * @return string
     */
    private function extractDomainFromName($name)
    {
        // If name is just "@", we need to get it from zone info
        if ($name === '@') {
            return $name; // Will be handled by the calling code
        }

        // Split the name into parts
        $parts = explode('.', $name);

        // If we have more than 2 parts, it's likely a subdomain
        if (count($parts) > 2) {
            // Get the last two parts for common TLDs (e.g., example.com)
            $domain = implode('.', array_slice($parts, -2));

            // Get special TLDs from config
            $specialTlds = config('tlds.special', [
                'com.au',
                'co.uk',
                'com.br',
                'co.jp',
                'com.mx',
                'co.nz',
                'com.sg'
            ]);

            foreach ($specialTlds as $tld) {
                if (str_ends_with($name, '.' . $tld)) {
                    $domain = implode('.', array_slice($parts, -3));
                    break;
                }
            }

            return $domain;
        }

        // If only 2 parts, it's already a root domain
        return $name;
    }

    /**
     * Get statistics for DNS records
     * 
     * @param string|null $domain
     * @return array
     */
    public function getStatistics($domain = null)
    {
        $query = $this->model->newQuery();

        if ($domain) {
            $query->where('domain', $domain);
        }

        return [
            'total_records' => $query->count(),
            'by_type' => $query->groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->pluck('count', 'type')
                ->toArray(),
            'proxied_count' => $query->where('proxied', true)->count(),
            'non_proxied_count' => $query->where('proxied', false)->count(),
        ];
    }
}