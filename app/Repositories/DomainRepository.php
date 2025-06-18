<?php

namespace App\Repositories;

use App\Models\Domain;
use App\Services\ApplicationLogger;

class DomainRepository extends BaseRepository
{
    protected $applicationLogger;

    public function __construct()
    {
        parent::__construct();
        $this->applicationLogger = app(ApplicationLogger::class);
    }

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
        $this->applicationLogger->logDomain('dns_lookup_started', [
            'domain' => $domain,
            'method' => 'realtime_fallback',
            'record_types' => ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS']
        ]);

        $dnsRecords = [];

        // Define DNS record types to query
        $recordTypes = [
            'A' => DNS_A,
            'AAAA' => DNS_AAAA,
            'CNAME' => DNS_CNAME,
            'MX' => DNS_MX,
            'TXT' => DNS_TXT,
            'NS' => DNS_NS
        ];

        foreach ($recordTypes as $type => $dnsConstant) {
            try {
                $this->applicationLogger->logDomain("dns_{$type}_records_query", [
                    'domain' => $domain,
                    'record_type' => $type,
                    'timeout' => 2
                ]);

                // Set very short timeout to prevent hanging
                $originalTimeout = ini_get('default_socket_timeout');
                ini_set('default_socket_timeout', 2); // 2 second timeout per record type

                // Use error suppression for each record type
                $records = @dns_get_record($domain, $dnsConstant);

                // Restore timeout immediately
                ini_set('default_socket_timeout', $originalTimeout);

                if ($records && is_array($records)) {
                    foreach ($records as $record) {
                        $formattedRecord = $this->formatDnsRecord($type, $record);
                        if ($formattedRecord) {
                            $dnsRecords[] = $formattedRecord;
                        }
                    }

                    $this->applicationLogger->logDomain('dns_lookup_success', [
                        'domain' => $domain,
                        'records_found' => count($records),
                        'record_type' => $type
                    ]);
                } else {
                    $this->applicationLogger->logDomain('dns_lookup_no_records', [
                        'domain' => $domain,
                        'record_type' => $type
                    ]);
                }

            } catch (\Exception $e) {
                $this->applicationLogger->logDomain('dns_lookup_failed', [
                    'domain' => $domain,
                    'error' => $e->getMessage(),
                    'record_type' => $type
                ], 'error');
                // Continue with other record types even if one fails
                continue;
            }
        }

        $this->applicationLogger->logDomain('dns_lookup_completed', [
            'domain' => $domain,
            'total_records' => count($dnsRecords),
            'record_types_found' => array_unique(array_column($dnsRecords, 'type'))
        ]);

        return $dnsRecords;
    }

    /**
     * Format DNS record based on type
     */
    private function formatDnsRecord($type, $record)
    {
        if (!is_array($record)) {
            return null;
        }

        $baseRecord = [
            'type' => $type,
            'name' => $record['host'] ?? '',
            'ttl' => $record['ttl'] ?? 0
        ];

        switch ($type) {
            case 'A':
                return array_merge($baseRecord, [
                    'content' => $record['ip'] ?? '',
                ]);

            case 'AAAA':
                return array_merge($baseRecord, [
                    'content' => $record['ipv6'] ?? '',
                ]);

            case 'CNAME':
                return array_merge($baseRecord, [
                    'content' => $record['target'] ?? '',
                ]);

            case 'MX':
                return array_merge($baseRecord, [
                    'content' => $record['target'] ?? '',
                    'priority' => $record['pri'] ?? 0,
                ]);

            case 'TXT':
                return array_merge($baseRecord, [
                    'content' => $record['txt'] ?? '',
                ]);

            case 'NS':
                return array_merge($baseRecord, [
                    'content' => $record['target'] ?? '',
                ]);

            default:
                return $baseRecord;
        }
    }
}
