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
}
