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
        if ($search == null) {
            return $this->model->where('is_locked', false)->get();
        }

        return $this->model->where('is_locked', false)->where('domain', 'LIKE', '%' . $search . '%')->get();
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
