<?php

namespace App\Repositories;

use App\Models\Card;
use App\Models\ListBoard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Enums\Boards;

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
        return $this->model->where('list_id', $listBoard)->max('position');
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
            if (!$newList) {
                throw new \Exception('List not found');
            }

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
                ->each(function ($c, $index) use ($newPosition) {
                    if ($index >= $newPosition - 1) {
                        $c->update(['position' => $index + 2]);
                    }
                });

            return $card;
        });
    }

    /**
     * Update positions for multiple cards at once
     * @param array $positions Array of objects containing card_id, list_id and new position
     * @return bool
     */
    public function updatePositions($positions)
    {
        return DB::transaction(function () use ($positions) {
            foreach ($positions as $position) {
                $card = $this->model->find($position['id']);

                // If card is moving to a different list
                if ($card->list_id != $position['list_id']) {
                    // Update positions in old list
                    $this->model->where('list_id', $card->list_id)
                        ->where('position', '>', $card->position)
                        ->decrement('position');

                    // Update positions in new list
                    $this->model->where('list_id', $position['list_id'])
                        ->where('position', '>=', $position['position'])
                        ->increment('position');
                }

                // Update card position and list
                $card->update([
                    'position' => $position['position'],
                    'list_id' => $position['list_id']
                ]);
            }
            return true;
        });
    }

    public function userHasAccess($cardId)
    {
        $user = Auth::user();
        $card = $this->show($cardId);

        if (!$card || !$card->listBoard || !$card->listBoard->board) {
            return false;
        }

        $board = $card->listBoard->board;

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
            ->where('board_id', $board->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function userCanEdit($cardId)
    {
        $user = Auth::user();
        $card = $this->show($cardId);

        if (!$card || !$card->listBoard || !$card->listBoard->board) {
            return false;
        }

        $board = $card->listBoard->board;

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
            ->where('board_id', $board->id)
            ->where('user_id', $user->id)
            ->whereIn('role', [Boards::ROLE_ADMIN, Boards::ROLE_MEMBER])
            ->exists();
    }
}
