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
        try {
            $match = [
                'domain' => $domain,
                'timestamp' => ['$gte' => new \MongoDB\BSON\UTCDateTime($diffTime->toDateTime())],
            ];

            if ($path !== 'all') {
                $match['path'] = $path;
            }

            $pipeline = [
                [
                    '$match' => $match,
                ],
                [
                    '$group' => [
                        '_id' => [
                            'domain' => '$domain',
                            'path' => '$path',
                        ],
                        'online_count' => ['$addToSet' => '$uuid'],
                    ],
                ],
                [
                    '$project' => [
                        'domain' => '$_id.domain',
                        'path' => '$_id.path',
                        'online_count' => ['$size' => '$online_count'],
                        '_id' => 0,
                    ],
                ],
            ];

            $result = $this->model->raw(function ($collection) use ($pipeline) {
                return $collection->aggregate($pipeline);
            })->map(function ($item) {
                return (object) $item;
            });

            return $result->isEmpty() ? collect([(object)['online_count' => 0]]) : $result;
        } catch (\Exception $e) {
            Log::error('Error in getCurrentUsersActive', [
                'domain' => $domain,
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
