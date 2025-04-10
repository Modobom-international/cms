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
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $action, ?array $details = null, ?int $userId = null)
    {
        $this->action = $action;
        $this->details = $details;
        $this->userId = $userId;
    }
}
