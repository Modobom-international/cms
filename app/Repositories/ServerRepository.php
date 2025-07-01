<?php

namespace App\Repositories;

use App\Models\Server;

class ServerRepository extends BaseRepository
{
    public function model()
    {
        return Server::class;
    }

    public function getByIp($ip)
    {
        return $this->model->where('ip', $ip)->first();
    }

    public function getByFilter($filter)
    {
        $query = $this->model;

        if (isset($filter['search'])) {
            $query->where('name', 'LIKE', '%' . $filter['search'] . '%');
        }

        $query = $query->orderBy('created_at', 'desc')->get();

        return $query;
    }

    public function listOnly()
    {
        return $this->model->get();
    }
}
