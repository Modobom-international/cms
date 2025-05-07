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

    public function getDomainBySearch($search)
    {
        $query = $this->model->with('sites.user')->where('is_locked', false);

        if ($search != null) {
            $query = $query->where('domain', 'LIKE', '%' . $search . '%');
        }

        $query = $query->get();

        return $query;
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
