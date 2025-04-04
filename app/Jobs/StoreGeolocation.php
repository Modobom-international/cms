<?php

namespace App\Jobs;

use App\Repositories\GeolocationRepository;
use App\Services\GeolocationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StoreGeolocation implements ShouldQueue
{
    use Queueable;

    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(GeolocationRepository $geolocationRepository, GeolocationService $geolocationService): void
    {
        $getCity = $geolocationService->getCityFromCoordinates($this->data['latitude'], $this->data['longitude']);
        if ($getCity) {
            $geolocationRepository->create([
                'latitude' => $this->data['latitude'],
                'longitude' => $this->data['longitude'],
                'city' => $getCity['address']['city'] ?? $getCity['address']['town'] ?? 'Unknown',
            ]);
        }
    }
}
