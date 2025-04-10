<?php

namespace App\Http\Controllers\API;

use App\Repositories\ActivityLogRepository;
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

    public function listActivityLog()
    {
        try {
            $activityLogs = $this->activityLogRepository->all();
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
