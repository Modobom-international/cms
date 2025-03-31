<?php

namespace App\Repositories;

use App\Models\Team;

class TeamRepository extends BaseRepository
{
    public function model()
    {
        return Team::class;
    }

    public function getTeams()
    {
        return $this->model->with('permissions')->get();
    }

    public function findTeam($id)
    {
        return $this->model->with('permissions')->where('id', $id)->first();
    }
}
