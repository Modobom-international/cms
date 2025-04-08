<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class GeolocationService
{
    public function getCityFromCoordinates(float $latitude, float $longitude)
    {
        try {
            $response = Http::timeout(5)->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'json',
                'lat' => $latitude,
                'lon' => $longitude,
                'zoom' => 10,
            ]);

            return $response->successful() ? $response->json() : null;
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    private function handleException(Exception $e)
    {
        if ($e->hasResponse()) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }

        return ['error' => 'Something went wrong'];
    }
}
