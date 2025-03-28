<?php

namespace App\Repositories;

use App\Models\Board;

class BoardRepository extends BaseRepository
{
    public function model()
    {
        return Board::class;
    }
    
    public function index($workspaceId)
    {
        return  $this->model->where('workspace_id', $workspaceId)->get();
    }
    
    public function createBoard($data)
    {
       return $this->model->create($data);
    }
    
    public function show($id)
    {
        return $this->model->with('owner')->where('id', $id)->first();
    }
    
    public function updateBoard($data, $id)
    {
        return $this->model->where('id', $id)->update($data);
    }
    
    public function destroy($id)
    {
        return $this->model->where('id', $id)->delete();
    }
    
}
