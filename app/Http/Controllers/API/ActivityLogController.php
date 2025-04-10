<?php

namespace App\Http\Controllers\API;

use App\Repositories\ActivityLogRepository;
use Illuminate\Http\Request;
use App\Traits\LogsActivity;
use App\Enums\Utility;

class ActivityLogController extends Controller
{
    use LogsActivity;

    protected $activityLogRepository;
    protected $utility;

    public function __construct(ActivityLogRepository $activityLogRepository, Utility $utility)
    {
        $this->activityLogRepository = $activityLogRepository;
        $this->utility = $utility;
    }

    public function listActivityLog(Request $request)
    {
        try {
            $date = $request->get('date') ?? $this->utility->getCurrentVNTime('Y-m-d');
            $user_id = $request->get('user_id');
            $action = $request->get('action');

            $filter = [
                'date' => $date,
                'user_id' => $user_id,
                'action' => $action
            ];

            $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input], 'Xem danh sách activity log');
            $activityLogs = $this->activityLogRepository->getByFilter($filter);
            $response = $this->utility->paginate($activityLogs);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Lấy danh sách activity log thành công',
                'type' => 'list_activity_log_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách activity log không thành công',
                'type' => 'list_activity_log_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
