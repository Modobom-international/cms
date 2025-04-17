<?php

namespace App\Repositories;

use App\Models\DueDate;

class DueDateRepository extends BaseRepository
{
    public function model()
    {
        return DueDate::class;
    }
    
    public function store($data)
    {
        return $this->model->create($data);
    }
    
    public function show($id)
    {
        return $this->model->with('card')->where('id', $id)->first();
    }
    
    public function update($data, $id)
    {
        return $this->model->where('id', $id)->update($data);
    }
    
    public function destroy($id)
    {
        return $this->model->where('id', $id)->delete();
    }
    
}
