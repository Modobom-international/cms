<?php

namespace App\Repositories;

use App\Models\Attachment;

class AttachmentRepository extends BaseRepository
{
    public function model()
    {
        return Attachment::class;
    }
    
    public function index($cardId)
    {
        return $this->model->where('card_id', $cardId)->with('user')->get();
    }
    
    public function store($data)
    {
       return $this->model->create($data);
    }
    
    public function show($id)
    {
        return $this->model->with('card')->where('id', $id)->first();
    }

    public function update($data, $id)
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function destroy($id)
    {
        return $this->model->where('id', $id)->delete();
    }
    
    
}
