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

    public function checkRoleAdmin($user, $workspace)
    {
        return $this->model->where('workspace_id', $workspace)
            ->where('user_id', $user)
            ->where('role', Workspace::ROLE_ADMIN)
            ->exists();
    }

    /**
     * Get all workspaces where a user is a member, including their role
     */
    public function getMembers($userId)
    {
        return $this->model->with([
            'workspace' => function ($query) {
                $query->with('owner');
            }
        ])
            ->where('user_id', $userId)
            ->get();
    }

    public function removeMember($workspaceId, $userId)
    {
        return $this->model->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Get a user's role in a specific workspace
     */
    public function getMemberRole($userId, $workspaceId)
    {
        return $this->model->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Get all members for multiple workspaces in a single query
     * 
     * @param array $workspaceIds
     * @return \Illuminate\Support\Collection
     */
    public function getMembersForWorkspaces(array $workspaceIds)
    {
        return $this->model
            ->with('users')
            ->whereIn('workspace_id', $workspaceIds)
            ->get();
    }
}
