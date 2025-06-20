<?php

namespace App\Repositories;

use App\Models\AppInformation;

class AppInformationRepository extends BaseRepository
{
    public function model()
    {
        return AppInformation::class;
    }

    public function getWithFilter($filters = [])
    {
        $query = $this->model;
        
        if (!empty($filters['app_name'])) {
            $query = $query->where('app_name', $filters['app_name']);
        }

        if (!empty($filters['app_name'])) {
            $query = $query->where('app_name', $filters['app_name']);
        }

        if (!empty($filters['app_name'])) {
            $query = $query->where('app_name', $filters['app_name']);
        }

        if (!empty($filters['app_name'])) {
            $query = $query->where('app_name', $filters['app_name']);
        }

        if (!empty($filters['app_name'])) {
            $query = $query->where('app_name', $filters['app_name']);
        }

        if (!empty($filters['app_name'])) {
            $query = $query->where('app_name', $filters['app_name']);
        }

        if (!empty($filters['app_name'])) {
            $query = $query->where('app_name', $filters['app_name']);
        }

        return $query->get();
    }
}
