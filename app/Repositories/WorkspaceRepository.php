<?php

namespace App\Repositories;
use App\Models\Workspace;

class WorkspaceRepository extends BaseRepository
{
    public function model()
    {
        return Workspace::class;
    }
    
    public function index()
    {
        return $this->model->get();
    }
    
    public function createWorkspace($data)
    {
       return $this->model->create($data);
    }
    
    public function show($id)
    {
        return $this->model->with('owner')->where('id', $id)->first();
    }
    
    public function updateWorkspace($data, $id)
    {
        return $this->model->where('id', $id)->update($data);
    }
    
    public function destroy($id)
    {
        return $this->model->where('id', $id)->delete();
    }
    
    public function checkExist($data)
    {
        return $this->model->find($data);
    }
    
}
