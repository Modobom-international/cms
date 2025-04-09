<?php

namespace App\Repositories;

use App\Models\HtmlSource;

class HtmlSourceRepository extends BaseRepository
{
    public function model()
    {
        return HtmlSource::class;
    }

    public function getApps()
    {
        return $this->model->select('app_id')->groupBy('app_id')->get();
    }

    public function getCountries()
    {
        return $this->model->select('country')->groupBy('country')->get();
    }

    public function getList($filter)
    {
        $query = $this->model;

        if (!empty($filter['country'])) {
            if ($filter['country'] != 'all') {
                $query = $query->where('country', $filter['country']);
            }
        }

        if (!empty($filter['app'])) {
            if ($filter['app'] != 'all') {
                $query = $query->where('app_id', $filter['app']);
            }
        }

        if (!empty($filter['date'])) {
            $query = $query->where('created_date', $filter['date']);
        }

        if (!empty($filter['device'])) {
            $query = $query->where('device_id', 'like', '%' . $filter['device'] . '%');
        }

        if (!empty($filter['text_source'])) {
            $query = $query->where('source', 'like', '%' . $filter['text_source'] . '%');
        }

        return $query->orderBy('id', 'desc')->paginate(20);
    }
}
