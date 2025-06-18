<?php

namespace App\Repositories;

use App\Models\Domain;
use Illuminate\Support\Facades\Log;

class DomainRepository extends BaseRepository
{
    public function model()
    {
        return Domain::class;
    }

    public function getFirstDomain()
    {
        return $this->model->where('is_locked', false)->first();
    }

    public function getAllDomain()
    {
        return $this->model->where('is_locked', false)->get();
    }

    public function findByDomain($domain)
    {
        return $this->model->where('domain', $domain)->where('is_locked', false)->first();
    }

    public function update($id, array $data)
    {
        $domain = $this->model->find($id);
        if ($domain) {
            $domain->update($data);
            return $domain;
        }
        return null;
    }

    public function updateOrCreate(array $attributes, array $values = [])
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    public function getDomainBySearch($search, $filters = [])
    {
        $query = $this->model->with('sites.user')->where('is_locked', false);

        if ($search != null) {
            $query = $query->where('domain', 'LIKE', '%' . $search . '%');
        }

        // Apply filters
        if (!empty($filters['status'])) {
            $query = $query->where('status', $filters['status']);
        }

        if (isset($filters['renewable'])) {
            $query = $query->where('renewable', $filters['renewable']);
        }

        if (!empty($filters['registrar'])) {
            $query = $query->where('registrar', 'LIKE', '%' . $filters['registrar'] . '%');
        }

        if (isset($filters['has_sites'])) {
            if ($filters['has_sites']) {
                $query = $query->whereHas('sites');
            } else {
                $query = $query->whereDoesntHave('sites');
            }
        }

        if (!empty($filters['time_expired'])) {
            $query = $query->where('time_expired', $filters['time_expired']);
        }

        if (!empty($filters['renew_deadline'])) {
            $query = $query->where('renew_deadline', $filters['renew_deadline']);
        }

        if (!empty($filters['registrar_created_at'])) {
            $query = $query->where('registrar_created_at', $filters['registrar_created_at']);
        }

        return $query->get();
    }

    public function getDomainByList($listDomain)
    {
        return $this->model->whereIn('domain', $listDomain)->where('is_locked', false)->pluck('domain')->toArray();
    }

    public function deleteByIsLocked($isLocked)
    {
        return $this->model->where('is_locked', $isLocked)->delete();
    }

    public function getDnsRecords($domain)
    {
        Log::debug("Starting DNS record retrieval for domain: {$domain}");
        $dnsRecords = [];

        try {
            // Get A records
            Log::debug("Retrieving A records for domain: {$domain}");
            $aRecords = dns_get_record($domain, DNS_A);
            Log::debug("Found " . (is_array($aRecords) ? count($aRecords) : 0) . " A records for domain: {$domain}");
            if ($aRecords) {
                foreach ($aRecords as $record) {
                    $dnsRecords[] = [
                        'type' => 'A',
                        'host' => $record['host'] ?? '',
                        'ip' => $record['ip'] ?? '',
                        'ttl' => $record['ttl'] ?? 0
                    ];
                }
            }

            // Get AAAA records (IPv6)
            Log::debug("Retrieving AAAA records for domain: {$domain}");
            $aaaaRecords = dns_get_record($domain, DNS_AAAA);
            Log::debug("Found " . (is_array($aaaaRecords) ? count($aaaaRecords) : 0) . " AAAA records for domain: {$domain}");
            if ($aaaaRecords) {
                foreach ($aaaaRecords as $record) {
                    $dnsRecords[] = [
                        'type' => 'AAAA',
                        'host' => $record['host'] ?? '',
                        'ipv6' => $record['ipv6'] ?? '',
                        'ttl' => $record['ttl'] ?? 0
                    ];
                }
            }

            // Get CNAME records
            Log::debug("Retrieving CNAME records for domain: {$domain}");
            $cnameRecords = dns_get_record($domain, DNS_CNAME);
            Log::debug("Found " . (is_array($cnameRecords) ? count($cnameRecords) : 0) . " CNAME records for domain: {$domain}");
            if ($cnameRecords) {
                foreach ($cnameRecords as $record) {
                    $dnsRecords[] = [
                        'type' => 'CNAME',
                        'host' => $record['host'] ?? '',
                        'target' => $record['target'] ?? '',
                        'ttl' => $record['ttl'] ?? 0
                    ];
                }
            }

            // Get MX records
            Log::debug("Retrieving MX records for domain: {$domain}");
            $mxRecords = dns_get_record($domain, DNS_MX);
            Log::debug("Found " . (is_array($mxRecords) ? count($mxRecords) : 0) . " MX records for domain: {$domain}");
            if ($mxRecords) {
                foreach ($mxRecords as $record) {
                    $dnsRecords[] = [
                        'type' => 'MX',
                        'host' => $record['host'] ?? '',
                        'target' => $record['target'] ?? '',
                        'priority' => $record['pri'] ?? 0,
                        'ttl' => $record['ttl'] ?? 0
                    ];
                }
            }

            // Get TXT records
            Log::debug("Retrieving TXT records for domain: {$domain}");
            $txtRecords = dns_get_record($domain, DNS_TXT);
            Log::debug("Found " . (is_array($txtRecords) ? count($txtRecords) : 0) . " TXT records for domain: {$domain}");
            if ($txtRecords) {
                foreach ($txtRecords as $record) {
                    $dnsRecords[] = [
                        'type' => 'TXT',
                        'host' => $record['host'] ?? '',
                        'txt' => $record['txt'] ?? '',
                        'ttl' => $record['ttl'] ?? 0
                    ];
                }
            }

            // Get NS records
            Log::debug("Retrieving NS records for domain: {$domain}");
            $nsRecords = dns_get_record($domain, DNS_NS);
            Log::debug("Found " . (is_array($nsRecords) ? count($nsRecords) : 0) . " NS records for domain: {$domain}");
            if ($nsRecords) {
                foreach ($nsRecords as $record) {
                    $dnsRecords[] = [
                        'type' => 'NS',
                        'host' => $record['host'] ?? '',
                        'target' => $record['target'] ?? '',
                        'ttl' => $record['ttl'] ?? 0
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to retrieve DNS records for domain {$domain}: " . $e->getMessage());
            throw new \Exception('Failed to retrieve DNS records: ' . $e->getMessage());
        }

        Log::debug("Successfully retrieved " . count($dnsRecords) . " total DNS records for domain: {$domain}");
        return $dnsRecords;
    }
}
