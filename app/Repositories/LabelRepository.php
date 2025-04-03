<?php

namespace App\Repositories;

use App\Models\Label;

class LabelRepository extends BaseRepository
{
    public function model()
    {
        return Label::class;
    }
    
    public function listLabels()
    {
        return $this->model->get();
    }
    
    public function createLabel($data)
    {
       return $this->model->create($data);
    }
    
    public function show($id)
    {
        return $this->model->where('id', $id)->first();
    }

    public function updateLabel($data, $id)
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function destroy($id)
    {
        return $this->model->where('id', $id)->delete();
    }
    
    
}
