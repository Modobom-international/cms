<?php

namespace Tests\Unit;

use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ActivityLoggerTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_dispatches_user_activity_logged_event()
    {
        Event::fake();
        $logger = new ActivityLogger();
        $logger->log('login', ['ip' => '127.0.0.1'], 'User logged in', 1, '127.0.0.1');

        Event::assertDispatched(\App\Events\UserActivityLogged::class, function ($event) {
            return $event->action === 'login' && $event->userId === 1;
        });
    }
} 