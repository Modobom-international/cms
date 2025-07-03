<?php

namespace App\Repositories;

use App\Models\Board;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function userHasAccess($boardId)
    {
        $user = Auth::user();

        return $user->boards()->where('board_id', $boardId)->exists();
    }
    
    public function countTotal()
    {
        return $this->model->count();
    }
    
    public function boardChart()
    {
        return $this->model
            ->select('boards.id', 'boards.name',
                DB::raw('COUNT(cards.id) as total'),
                DB::raw('SUM(CASE WHEN due_dates.is_completed = true THEN 1 ELSE 0 END) as done')
            )
            ->leftJoin('lists', 'lists.board_id', '=', 'boards.id')
            ->leftJoin('cards', 'cards.list_id', '=', 'lists.id')
            ->leftJoin('due_dates', 'due_dates.card_id', '=', 'cards.id')
            ->groupBy('boards.id', 'boards.name')
            ->get()
            ->map(function ($item) {
                return [
                    'board_name' => $item->name,
                    'total' => $item->total,
                    'done' => $item->done,
                    'percent' => $item->total > 0 ? round($item->done / $item->total * 100) : 0,
                ];
            });
    }
    
    public function progressPerBoard()
    {
        $boards = $this->model->with(['lists.cards.dueDate'])->get();
        return $boards->map(function ($board) {
            $cards = $board->lists->flatMap->cards;
            $total = $cards->count();
            $done = $cards->filter(fn($card) => $card->dueDate?->is_completed)->count();
            return [
                'board_name' => $board->name,
                'total_cards' => $total,
                'completion_percent' => $total > 0 ? round($done / $total * 100) : 0,
            ];
        })->sortByDesc('completion_percent')->values();
    }
    
}
