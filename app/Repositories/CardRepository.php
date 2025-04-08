<?php

namespace App\Repositories;

use App\Models\Card;
use App\Models\ListBoard;
use Illuminate\Support\Facades\DB;

class CardRepository extends BaseRepository
{
    public function model()
    {
        return Card::class;
    }
    
    public function createCard($data)
    {
       return $this->model->create($data);
    }
    
    public function maxPosition($listBoard)
    {
        return  $this->model->where('list_id',$listBoard)->max('position');
    }
    
    public function show($id)
    {
        return $this->model->with('listBoard')->where('id', $id)->first();
    }

    public function updateCard($data, $id)
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function destroy($id)
    {
        return $this->model->where('id', $id)->delete();
    }
    
    public function moveCard($cardId, $newListId, $newPosition)
    {
        return DB::transaction(function () use ($cardId, $newListId, $newPosition) {
            $card = Card::findOrFail($cardId);
            $oldListId = $card->list_id;
            
            // Kiểm tra list mới có tồn tại không
            $newList = ListBoard::findOrFail($newListId);
            
            // Cập nhật card với list mới và vị trí mới
            $card->list_id = $newListId;
            $card->position = $newPosition;
            $card->save();
            
            // Cập nhật lại vị trí của Card trong list cũ
            Card::where('list_id', $oldListId)
                ->orderBy('position')
                ->get()
                ->each(function ($c, $index) {
                    $c->update(['position' => $index + 1]);
                });
            
            // Cập nhật lại vị trí của Card trong list mới
            Card::where('list_id', $newListId)
                ->where('id', '!=', $cardId)
                ->orderBy('position')
                ->get()
                ->each(function ($c, $index) {
                    if ($index >= $newPosition - 1) {
                        $c->update(['position' => $index + 2]);
                    }
                });
            
            return $card;
        });
    }
    
}
