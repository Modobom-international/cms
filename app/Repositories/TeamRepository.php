<?php

namespace App\Repositories;

use App\Models\Team;

class TeamRepository extends BaseRepository
{
    public function model()
    {
        return Team::class;
    }

    public function getTeamByFilter($filter = [])
    {
        $query = $this->model->with('permissions');

        if (isset($filter['search'])) {
            $query->where('name', 'LIKE', '%' . $filter['search'] . '%');
        }

        return $query->get();
    }

    public function findTeam($id)
    {
        return $this->model->with('permissions')->where('id', $id)->first();
    }

    public function getList()
    {
        return $this->model->get();
    }
}
