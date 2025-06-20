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
            $query = $query->whereIn('app_name', $filters['app_name']);
        }

        if (!empty($filters['os_name'])) {
            $query = $query->whereIn('os_name', $filters['os_name']);
        }

        if (!empty($filters['os_version'])) {
            $query = $query->whereIn('os_version', $filters['os_version']);
        }

        if (!empty($filters['app_version'])) {
            $query = $query->whereIn('app_version', $filters['app_version']);
        }

        if (!empty($filters['category'])) {
            $query = $query->whereIn('category', $filters['category']);
        }

        if (!empty($filters['platform'])) {
            $query = $query->whereIn('platform', $filters['platform']);
        }

        if (!empty($filters['country'])) {
            $query = $query->whereIn('country', $filters['country']);
        }

        if (!empty($filters['event_name'])) {
            $query = $query->whereIn('event_name', $filters['event_name']);
        }

        return $query->get();
    }
}
