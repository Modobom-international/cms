<?php

namespace App\Http\Controllers\API;

use App\Repositories\ActivityLogRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\LogsActivity;
use App\Enums\Utility;
use App\Enums\ActivityAction;
use Exception;

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
            $input = $request->all();
            $date = $request->get('date') ?? $this->utility->getCurrentVNTime('Y-m-d');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $user_id = $request->get('user_id');
            $action = $request->get('action');
            $group_action = $request->get('group_action');
            $search = $request->get('search');
            $pageSize = $request->get('pageSize') ?? 10;
            $page = $request->get('page') ?? 1;

            $filter = [
                'date' => $date,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'user_id' => $user_id,
                'action' => $action,
                'group_action' => $group_action,
                'search' => $search,
            ];

            $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input], 'Xem danh sách activity log');
            $activityLogs = $this->activityLogRepository->getByFilter($filter);
            $data = $this->utility->paginate($activityLogs, $pageSize, $page);

            return response()->json([
                'success' => true,
                'data' => [
                    'activities' => $data->items(),
                    'pagination' => [
                        'current_page' => $data->currentPage(),
                        'per_page' => $data->perPage(),
                        'total' => $data->total(),
                        'last_page' => $data->lastPage(),
                        'from' => $data->firstItem(),
                        'to' => $data->lastItem(),
                    ],
                    'filters_applied' => array_filter($filter, function ($value) {
                        return $value !== null && $value !== '';
                    })
                ],
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

    public function getActivityStats(Request $request)
    {
        try {
            $input = $request->all();
            $date = $request->get('date') ?? $this->utility->getCurrentVNTime('Y-m-d');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $user_id = $request->get('user_id');

            $filter = [
                'date' => $date,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'user_id' => $user_id,
            ];

            $stats = $this->activityLogRepository->getActivityStats($filter);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_activities' => $stats['total_activities'],
                    'actions_by_group' => $stats['actions_by_group'],
                    'top_users' => $stats['top_users'],
                    'filters_applied' => array_filter($filter, function ($value) {
                        return $value !== null && $value !== '';
                    })
                ],
                'message' => 'Lấy thống kê activity log thành công',
                'type' => 'get_activity_stats_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy thống kê activity log không thành công',
                'type' => 'get_activity_stats_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAvailableFilters()
    {
        try {
            $filters = [
                'group_actions' => [
                    [
                        'value' => 'site_management',
                        'label' => 'Quản lý Site',
                        'description' => 'Các hoạt động liên quan đến quản lý site'
                    ],
                    [
                        'value' => 'page_management',
                        'label' => 'Quản lý Trang',
                        'description' => 'Các hoạt động liên quan đến quản lý trang'
                    ],
                    [
                        'value' => 'attendance_management',
                        'label' => 'Quản lý Chấm công',
                        'description' => 'Các hoạt động liên quan đến chấm công'
                    ],
                    [
                        'value' => 'attendance_complaints',
                        'label' => 'Khiếu nại Chấm công',
                        'description' => 'Các hoạt động liên quan đến khiếu nại chấm công'
                    ],
                    [
                        'value' => 'board_management',
                        'label' => 'Quản lý Bảng',
                        'description' => 'Các hoạt động liên quan đến quản lý bảng'
                    ],
                    [
                        'value' => 'cloudflare_operations',
                        'label' => 'Thao tác Cloudflare',
                        'description' => 'Các hoạt động liên quan đến Cloudflare'
                    ],
                    [
                        'value' => 'domain_operations',
                        'label' => 'Thao tác Domain',
                        'description' => 'Các hoạt động liên quan đến domain'
                    ],
                    [
                        'value' => 'general_operations',
                        'label' => 'Thao tác Chung',
                        'description' => 'Các hoạt động chung khác'
                    ]
                ],
                'date_filters' => [
                    'today' => 'Hôm nay',
                    'yesterday' => 'Hôm qua',
                    'this_week' => 'Tuần này',
                    'last_week' => 'Tuần trước',
                    'this_month' => 'Tháng này',
                    'last_month' => 'Tháng trước',
                    'custom_range' => 'Khoảng thời gian tùy chỉnh'
                ],
                'sort_options' => [
                    'created_at_desc' => 'Thời gian tạo (Mới nhất)',
                    'created_at_asc' => 'Thời gian tạo (Cũ nhất)',
                    'action_asc' => 'Hành động (A-Z)',
                    'action_desc' => 'Hành động (Z-A)',
                    'user_name_asc' => 'Tên người dùng (A-Z)',
                    'user_name_desc' => 'Tên người dùng (Z-A)'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $filters,
                'message' => 'Lấy danh sách bộ lọc thành công',
                'type' => 'get_available_filters_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách bộ lọc không thành công',
                'type' => 'get_available_filters_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportActivityLog(Request $request)
    {
        try {
            $input = $request->all();
            $date = $request->get('date') ?? $this->utility->getCurrentVNTime('Y-m-d');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $user_id = $request->get('user_id');
            $action = $request->get('action');
            $group_action = $request->get('group_action');
            $search = $request->get('search');
            $format = $request->get('format', 'csv'); // csv, excel, json

            $filter = [
                'date' => $date,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'user_id' => $user_id,
                'action' => $action,
                'group_action' => $group_action,
                'search' => $search,
            ];

            $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input, 'export_format' => $format], 'Xuất activity log');
            $activityLogs = $this->activityLogRepository->getByFilter($filter);

            // For now, return JSON format. You can implement CSV/Excel export later
            $exportData = $activityLogs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'action_label' => $log->action_label,
                    'group_action' => $log->group_action,
                    'description' => $log->description,
                    'user_id' => $log->user_id,
                    'user_email' => $log->user_email,
                    'user_name' => $log->user_name,
                    'created_at' => $log->formatted_created_at,
                    'details' => $log->details
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'export_data' => $exportData,
                    'total_records' => $exportData->count(),
                    'export_format' => $format,
                    'filters_applied' => array_filter($filter, function ($value) {
                        return $value !== null && $value !== '';
                    })
                ],
                'message' => 'Xuất activity log thành công',
                'type' => 'export_activity_log_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xuất activity log không thành công',
                'type' => 'export_activity_log_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
