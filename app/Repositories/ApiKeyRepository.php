<?php

namespace App\Repositories;

use App\Models\ApiKey;
use App\Models\Server;

class ApiKeyRepository extends BaseRepository
{
    public function model()
    {
        return ApiKey::class;
    }

    public function getByUser($userId)
    {
        return $this->model->where('user_id', $userId)->get();
    }

    public function getByUserAndId($userId, $id)
    {
        return $this->model->where('user_id', $userId)->where('id', $id)->first();
    }

    public function createApiKey($data)
    {
        return $this->model->create($data);
    }

    public function updateApiKey($id, $data)
    {
        $apiKey = $this->model->find($id);
        if ($apiKey) {
            $apiKey->update($data);
            return $apiKey;
        }
        return null;
    }

    public function deleteApiKey($id)
    {
        $apiKey = $this->model->find($id);
        if ($apiKey) {
            return $apiKey->delete();
        }
        return false;
    }

    public function getByServer($serverId)
    {
        return $this->model->whereHas('servers', function ($query) use ($serverId) {
            $query->where('server_id', $serverId);
        })->first();
    }

    public function getByServerIp($ip)
    {
        return $this->model->whereHas('servers', function ($query) use ($ip) {
            $query->whereHas('server', function ($q) use ($ip) {
                $q->where('ip', $ip);
            });
        })->first();
    }

    public function attachToServer($apiKeyId, $serverId)
    {
        $apiKey = $this->model->find($apiKeyId);
        $server = Server::find($serverId);
        
        if ($apiKey && $server) {
            return $server->apiKeys()->attach($apiKeyId);
        }
        return false;
    }

    public function detachFromServer($apiKeyId, $serverId)
    {
        $apiKey = $this->model->find($apiKeyId);
        $server = Server::find($serverId);
        
        if ($apiKey && $server) {
            return $server->apiKeys()->detach($apiKeyId);
        }
        return false;
    }

    public function verifyKeyForServer($key, $serverId)
    {
        $apiKey = $this->getByServer($serverId);
        
        if (!$apiKey) {
            return false;
        }

        return $apiKey->verifyKey($key) && $apiKey->isValid();
    }

    public function updateLastUsed($id)
    {
        $apiKey = $this->model->find($id);
        if ($apiKey) {
            return $apiKey->update(['last_used_at' => now()]);
        }
        return false;
    }
} 