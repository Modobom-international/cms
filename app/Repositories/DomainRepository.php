<?php

namespace App\Repositories;

use App\Models\Domain;

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
        return $this->model->get(); // Include all domains, regardless of is_locked status
    }

    public function findByDomain($domain)
    {
        return $this->model->where('domain', $domain)->first();
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
        $query = $this->model->with('sites.user');

        if ($search != null) {
            $query = $query->where('domain', 'LIKE', '%' . $search . '%');
        }

        // Apply filters
        if (!empty($filters['status'])) {
            $query = $query->where('status', $filters['status']);
        }

        if (isset($filters['is_locked'])) {
            $query = $query->where('is_locked', $filters['is_locked']);
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
        return $this->model->whereIn('domain', $listDomain)->pluck('domain')->toArray();
    }

    public function deleteByIsLocked($isLocked)
    {
        return $this->model->where('is_locked', $isLocked)->delete();
    }

    public function getDnsRecords($domain)
    {
        $dnsRecords = [];

        try {
            // Get A records
            $aRecords = dns_get_record($domain, DNS_A);
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
            $aaaaRecords = dns_get_record($domain, DNS_AAAA);
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
            $cnameRecords = dns_get_record($domain, DNS_CNAME);
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
            $mxRecords = dns_get_record($domain, DNS_MX);
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
            $txtRecords = dns_get_record($domain, DNS_TXT);
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
            $nsRecords = dns_get_record($domain, DNS_NS);
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
            throw new \Exception('Failed to retrieve DNS records: ' . $e->getMessage());
        }

        return $dnsRecords;
    }
}
