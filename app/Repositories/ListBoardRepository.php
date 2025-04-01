<?php

namespace App\Repositories;

use App\Models\ListBoard;
use Illuminate\Support\Facades\Auth;

class ListBoardRepository extends BaseRepository
{
    public function model()
    {
        return ListBoard::class;
    }
    
    public function getListsByBoard($boardId)
    {
        // Kiểm tra user có quyền truy cập board không
        if (!$this->userHasAccess($boardId)) {
            return null; // Trả về null để xử lý trong Controller
        }
        
        // Lấy danh sách list thuộc board
        return $this->model->where('board_id', $boardId)
            ->orderBy('position', 'asc')
            ->get();
    }
    
    private function userHasAccess($boardId)
    {
        return Auth::user()->boards()->where('boards.id', $boardId)->exists();
    }
    
    public function createListBoard($data)
    {
       return $this->model->create($data);
    }
    
    public function maxPosition($board)
    {
        return  $this->model->where('board_id', $board)->max('position');
    }
    
    public function show($id)
    {
        return $this->model->where('id', $id)->first();
    }

    public function updateListBoard($data, $id)
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function destroy($id)
    {
        return $this->model->where('id', $id)->delete();
    }
//
//    public function userHasAccess($boardId)
//    {
//        $user = Auth::user();
//
//        return $user->boards()->where('board_id', $boardId)->exists();
//    }
    
}
