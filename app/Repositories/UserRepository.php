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
    
    public function getDataUser($dataUser)
    {
        return $this->model->where('id', $dataUser)->first();
    }

    public function update($dataUser, $id)
    {
        return $this->model->where('id', $id)->update($dataUser);
    }

    public function getInfo($dataEmail)
    {
        return $this->model->where('email', $dataEmail)->first();
    }

    public function updatePassword($email, $input)
    {
        return $this->model->where('email', $email)->update($input);
    }
}
