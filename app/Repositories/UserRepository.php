<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserRepository extends BaseRepository
{
    public function model()
    {
        return User::class;
    }

    public function createUser($dataUser)
    {
        return $this->model->create($dataUser);
    }

    public function getUserByID($id)
    {
        return $this->model->where('id', $id)->first();
    }

    public function update($dataUser, $id)
    {
        return $this->model->where('id', $id)->update($dataUser);
    }

    public function getUserByEmail($dataEmail)
    {
        return $this->model->where('email', $dataEmail)->first();
    }

    public function showInfo($id)
    {
        return $this->model->with('workspaces')->where('id', $id)->first();
    }

    public function updatePassword($email, $input)
    {
        return $this->model->where('email', $email)->update($input);
    }

    public function find($user)
    {
        return $this->model->find($user);
    }

    public function getUsersByFilter($filter = [])
    {
        $query = $this->model->with('teams');

        if (isset($filter['team'])) {
            $query->whereHas('teams', function ($q) use ($filter) {
                $q->where('name', 'LIKE', '%' . $filter['team'] . '%');
            });
        }

        if (isset($filter['search'])) {
            $query->where('name', 'LIKE', '%' . $filter['search'] . '%');
        }

        $users = $query->get();

        $users = $users->map(function ($user) {
            if(isset($user->teams->name)) {
                $user->team_user = $user->teams->name;
            } else {
                $user->team_user = null;
            }
            
            return $user;
        });

        return $users;
    }
    
    public function topUser()
    {
        return $this->model->select('users.id', 'users.name', DB::raw('COUNT(card_users.card_id) as total'))
            ->join('card_users', 'users.id', '=', 'card_users.user_id')
            ->join('due_dates', 'due_dates.card_id', '=', 'card_users.card_id')
            ->where('due_dates.is_completed', true)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->take(3)
            ->get()
            ->map(function ($user) {
                return [
                    'user' => $user->name,
                    'task_done' => $user->total,
                ];
            });
        
    }
}
