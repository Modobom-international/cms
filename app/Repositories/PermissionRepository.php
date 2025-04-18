<?php

namespace App\Repositories;

use App\Models\Permission;

class PermissionRepository extends BaseRepository
{
    public function model()
    {
        return Permission::class;
    }

    public function updateOrCreate($data)
    {
        return $this->model->updateOrCreate(
            ['name' => $data['name']],
            [
                'prefix' => $data['prefix'] ?? null,
                'description' => $data['description'] ?? null,
            ]
        );
    }
}
