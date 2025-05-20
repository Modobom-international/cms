<?php

namespace App\Repositories;

use App\Models\Checklist;

class CheckListRepository extends BaseRepository
{
    public function model()
    {
        return Checklist::class;
    }

    public function index($cardId)
    {
        return $this->model->where('card_id', $cardId)->with('items')->get();
    }

    public function storeCheckList($data)
    {
        return $this->model->create($data);
    }

    public function show($id)
    {
        return $this->model->with('card')->where('id', $id)->first();
    }

    public function updateCheckList($data, $id)
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function destroy($id)
    {
        return $this->model->where('id', $id)->delete();
    }


}
