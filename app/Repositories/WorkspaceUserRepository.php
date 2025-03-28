<?php

namespace App\Repositories;
use App\Enums\Workspace;
use App\Models\WorkspaceUser;

class WorkspaceUserRepository extends BaseRepository
{
    public function model()
    {
        return WorkspaceUser::class;
    }
    
    public function index()
    {
        return $this->model->get();
    }
    
    public function createWorkSpaceUser($data)
    {
       return $this->model->create($data);
    }
    
    public function checkMemberExist($user, $workspace)
    {
        return $this->model->where('workspace_id', $workspace)
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
    
    
    public function getMembers($workspaceId)
    {
        return $this->model->with([
            'users' => function ($query) {
                $query->select('id', 'name', 'email', 'profile_photo_path');
            }
        ])
            ->where('workspace_id', $workspaceId)
            ->get();
    }
    
    public function removeMember($workspaceId, $userId)
    {
        return $this->model->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->delete();
    }
    
}
