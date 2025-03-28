<?php

namespace App\Repositories;
use App\Enums\Workspace;
use App\Models\BoardUser;

class BoardUserRepository extends BaseRepository
{
    public function model()
    {
        return BoardUser::class;
    }
    
    public function index()
    {
        return $this->model->get();
    }
    
    public function createBoardUser($data)
    {
        return $this->model->create($data);
    }
    
    public function checkMemberExist($user, $boardId)
    {
        return $this->model->where('board_id', $boardId)
            ->where('user_id', $user)
            ->exists();
    }
    
    public function checkRoleAdmin($user, $workspace )
    {
        return $this->model->where('workspace_id', $workspace)
            ->where('user_id', $user)
            ->where('role', Workspace::ROLE_ADMIN)
            ->exists();
    }
    
    
    public function getMembers($boardId)
    {
        return $this->model->with([
            'users' => function ($query) {
                $query->select('id', 'name', 'email', 'profile_photo_path');
            }
        ])
            ->where('board_id', $boardId)
            ->get();
    }
    
    public function removeMember($boardId, $userId)
    {
        return $this->model->where('board_id', $boardId)
            ->where('user_id', $userId)
            ->delete();
    }
    
}
