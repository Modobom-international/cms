<?php

namespace App\Traits;

use App\Events\NotificationSystem as EventsNotificationSystem;
use App\Models\NotificationSystem;

trait NotificationTrait
{
    public function sendNotification(NotificationSystemRepository $notificationSystemRepository, string $email, string $message): ?NotificationSystem
    {
        try {
            $data = [
                'email' => $email,
                'message' => $message,
                'unread' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $notification = $notificationSystemRepository->create($data);

            event(new EventsNotificationSystem($data));

            return $notification;
        } catch (\Exception $e) {
            \Log::error('Failed to send notification: ' . $e->getMessage());
            return null;
        }
    }
}
