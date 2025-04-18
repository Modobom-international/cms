<?php

namespace App\Repositories;

use App\Models\LogActivityUser;

class LogActivityUserRepository extends BaseRepository
{
    public function model()
    {
        return LogActivityUser::class;
    }
    
    public function listLog($cardId)
    {
        return $this->model->where('card_id', $cardId)
            ->orderByDesc('created_at')
            ->get();
    }
    
    public function create($data)
    {
       return $this->model->create($data);
    }
 
    
}
