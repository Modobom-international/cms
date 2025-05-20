<?php

namespace App\Repositories;

use App\Models\ListBoard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\Boards;
use App\Models\Board;

class ListBoardRepository extends BaseRepository
{
    public function model()
    {
        return ListBoard::class;
    }

    public function getListsByBoard($boardId)
    {
        // Lấy danh sách list thuộc board kèm theo cards đã sắp xếp
        return $this->model->where('board_id', $boardId)
            ->with([
                'cards' => function ($query) {
                    $query->orderBy('position', 'asc');
                }
            ])
            ->orderBy('position', 'asc')
            ->get();
    }

    public function userHasAccess($boardId)
    {
        $user = Auth::user();

        // Get the board directly from the Board model
        $board = Board::find($boardId);

        if (!$board) {
            return false;
        }

        // First check if user has access to the workspace through workspace_users table
        $hasWorkspaceAccess = DB::table('workspace_users')
            ->where('workspace_id', $board->workspace_id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$hasWorkspaceAccess) {
            return false;
        }

        // If board is public and user has workspace access, allow viewing
        if ($board->visibility === Boards::BOARD_PUBLIC) {
            return true;
        }

        // For private boards, check if user is a board member through board_users table
        return DB::table('board_users')
            ->where('board_id', $boardId)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function userCanEdit($boardId)
    {
        $user = Auth::user();

        // Get the board directly from the Board model
        $board = Board::find($boardId);

        if (!$board) {
            return false;
        }

        // First check if user has access to the workspace through workspace_users table
        $hasWorkspaceAccess = DB::table('workspace_users')
            ->where('workspace_id', $board->workspace_id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$hasWorkspaceAccess) {
            return false;
        }

        // Check if user has admin or member role in the board through board_users table
        return DB::table('board_users')
            ->where('board_id', $boardId)
            ->where('user_id', $user->id)
            ->whereIn('role', [Boards::ROLE_ADMIN, Boards::ROLE_MEMBER])
            ->exists();
    }

    public function createListBoard($data)
    {
        return $this->model->create($data);
    }

    public function maxPosition($board)
    {
        return $this->model->where('board_id', $board)->max('position');
    }

    public function show($id)
    {
        return $this->model->with([
            'cards' => function ($query) {
                $query->orderBy('position', 'asc');
            }
        ])->where('id', $id)->first();
    }

    public function updateListBoard($data, $id)
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function destroy($id)
    {
        return $this->model->where('id', $id)->delete();
    }

    /**
     * Update positions for multiple lists at once
     * @param array $positions Array of objects containing list_id and new position
     * @return bool
     */
    public function updatePositions($positions)
    {
        return DB::transaction(function () use ($positions) {
            foreach ($positions as $position) {
                $this->model->where('id', $position['id'])
                    ->update(['position' => $position['position']]);
            }
            return true;
        });
    }
}
