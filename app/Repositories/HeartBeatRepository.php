<?php

namespace App\Repositories;

use App\Models\HeartBeat;

class HeartBeatRepository extends BaseRepository
{
    public function model()
    {
        return HeartBeat::class;
    }

    public function getByUuidAndDomain($uuid, $domain)
    {
        return $this->model->where('uuid', $uuid)
            ->where('domain', $domain)
            ->first();
    }

    public function getCurrentUsersActive($domain, $path, $diffTime)
    {
        $query = $this->model->where('domain', $domain)
            ->where('created_at', '>=', $diffTime);

        if ($path !== 'all') {
            $query->where('path', 'LIKE', '%' . $path . '%');
        }

        $uuids = $query->pluck('uuid')->unique();

        return [
            'online_count' => $uuids->count(),
        ];
    }
}
