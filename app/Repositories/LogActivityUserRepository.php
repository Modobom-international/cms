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
        return $this->model->where('card_id', $cardId)->orderByDesc('created_at')->get();
    }
    
    public function create($data)
    {
       return $this->model->create($data);
    }
    
    public function checkExistLog($user, $cardId)
    {
        return $this->model->where('user_id', $user)
            ->where('card_id', $cardId)
            ->where('target_type', 'create check item')
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();
    }
 
}
