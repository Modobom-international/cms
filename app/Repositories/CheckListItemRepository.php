<?php

namespace App\Repositories;

use App\Models\CheckListItem;

class CheckListItemRepository extends BaseRepository
{
    public function model()
    {
        return CheckListItem::class;
    }

    public function index($cardId)
    {
        return $this->model->where('card_id', $cardId)->with('item')->get();
    }

    public function storeItem($data)
    {
        return $this->model->create($data);
    }

    public function show($id)
    {
        return $this->model->with('checklist')->where('id', $id)->first();
    }

    public function updateItem($data, $id)
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function destroy($id)
    {
        return $this->model->where('id', $id)->delete();
    }


}
