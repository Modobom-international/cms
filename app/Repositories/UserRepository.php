<?php

namespace App\Repositories;

use App\Models\User;

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
    
    public function getUserByID($dataUser)
    {
        return $this->model->where('id', $dataUser)->first();
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
}
