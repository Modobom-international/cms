<?php

namespace App\Repositories;

use App\Models\NotificationSystem;

class NotificationSystemRepository extends BaseRepository
{
    public function model()
    {
        return NotificationSystem::class;
    }

    public function getByEmail($email)
    {
        return $this->model->where('email', $email)->orderBy('unread')->limit(4)->get();
    }
}
