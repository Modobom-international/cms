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
        return $this->model->where('is_locked', false)->get();
    }

    public function getByDomain($domain)
    {
        return $this->model->where('domain', $domain)->first();
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
}
