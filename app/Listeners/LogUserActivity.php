<?php

namespace App\Listeners;

use App\Events\UserActivityLogged;
use App\Repositories\ActivityLogRepository;

class LogUserActivity
{
    protected $activityLogRepository;
    /**
     * Create the event listener.
     */
    public function __construct(ActivityLogRepository $activityLogRepository)
    {
        $this->activityLogRepository = $activityLogRepository;
    }

    /**
     * Handle the event.
     */
    public function handle(UserActivityLogged $event): void
    {
        $this->activityLogRepository->create([
            'action' => $event->action,
            'details' => $event->details,
            'user_id' => $event->userId,
        ]);
    }
}
