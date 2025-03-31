<?php

namespace App\Repositories;

use App\Models\Salary;

class SalaryRepository extends BaseRepository
{
    public function model()
    {
        return Salary::class;
    }

    public function getSalaryByUserID($user_id)
    {
        return $this->model->where('user_id', $user_id)->first();
    }
}
