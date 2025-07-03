<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserActivityLogged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $action;
    public $details;
    public $description;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $action, array $details, string $description, ?int $userId, string $ip)
    {
        $this->action = $action;
        $this->details = $details;
        $this->description = $description;
        $this->userId = $userId;
        $this->ip = $ip;
    }
}
