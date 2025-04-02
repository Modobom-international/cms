<?php

namespace App\Repositories;

use App\Models\Card;

class CardRepository extends BaseRepository
{
    public function model()
    {
        return Card::class;
    }
    
    public function createCard($data)
    {
       return $this->model->create($data);
    }
    
    public function maxPosition($listBoard)
    {
        return  $this->model->where('list_id',$listBoard)->max('position');
    }
    
    public function show($id)
    {
        return $this->model->where('id', $id)->first();
    }

    public function updateCard($data, $id)
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function destroy($id)
    {
        return $this->model->where('id', $id)->delete();
    }
    
}
