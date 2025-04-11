<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\NotificationSystemRepository;

class NotificationController extends Controller
{
    protected $notificationSystemRepository;

    public function __construct(NotificationSystemRepository $notificationSystemRepository)
    {
        $this->notificationSystemRepository = $notificationSystemRepository;
    }

    public function getNotifications(Request $request)
    {
        try {
            $user = $request->user();
            $notifications = $this->notificationSystemRepository->getByEmail($user->email);

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'message' => 'Lấy notifications thành công',
                'type' => 'get_notifications_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'notifications' => null,
                'message' => 'Lấy notifications không thành công',
                'type' => 'get_notifications_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
