<?php

namespace App\Repositories;

use App\Models\ListBoard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        return Auth::user()->boards()->where('boards.id', $boardId)->exists();
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
