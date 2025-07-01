<?php

namespace App\Http\Controllers\API;

use App\Repositories\ActivityLogRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\LogsActivity;
use App\Enums\Utility;
use App\Enums\ActivityAction;
use Exception;
use Illuminate\Validation\ValidationException;

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

            // Build filter array with all possible parameters
            $filter = [
                'date' => $request->get('date'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'user_id' => $request->get('user_id'),
                'action' => $request->get('action'),
                'group_action' => $request->get('group_action'),
                'search' => $request->get('search'),
                'page' => $request->get('page', 1),
                'pageSize' => $request->get('pageSize', 20),
                'sort_field' => $request->get('sort_field', 'created_at'),
                'sort_direction' => $request->get('sort_direction', 'desc'),
            ];

            // Remove null values
            $filter = array_filter($filter, function ($value) {
                return $value !== null && $value !== '';
            });

            // $this->logActivity(ActivityAction::ACCESS_VIEW, ['filters' => $input], 'Xem danh sách activity log');
            $paginator = $this->activityLogRepository->getByFilter($filter);

            return response()->json([
                'success' => true,
                'data' => [
                    'activities' => $paginator->items(),
                    'pagination' => [
                        'current_page' => $paginator->currentPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                        'last_page' => $paginator->lastPage(),
                        'from' => $paginator->firstItem(),
                        'to' => $paginator->lastItem(),
                        'has_more_pages' => $paginator->hasMorePages(),
                        'on_first_page' => $paginator->onFirstPage(),
                    ],
                    'filters_applied' => $filter,
                    'sort' => [
                        'field' => $filter['sort_field'] ?? 'created_at',
                        'direction' => $filter['sort_direction'] ?? 'desc'
                    ]
                ],
                'message' => 'Lấy danh sách activity log thành công',
                'type' => 'list_activity_log_success',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu đầu vào không hợp lệ',
                'type' => 'list_activity_log_validation_error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách activity log không thành công',
                'type' => 'list_activity_log_fail',
                'error' => config('app.debug') ? $e->getMessage() : 'Đã xảy ra lỗi hệ thống'
            ], 500);
        }
    }

    public function getActivityStats(Request $request)
    {
        try {
            $input = $request->all();

            $filter = [
                'date' => $request->get('date'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'user_id' => $request->get('user_id'),
            ];

            // Remove null values
            $filter = array_filter($filter, function ($value) {
                return $value !== null && $value !== '';
            });

            $stats = $this->activityLogRepository->getActivityStats($filter);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_activities' => $stats['total_activities'],
                    'actions_by_group' => $stats['actions_by_group'],
                    'top_users' => $stats['top_users'],
                    'daily_activities' => $stats['daily_activities'],
                    'filters_applied' => $filter
                ],
                'message' => 'Lấy thống kê activity log thành công',
                'type' => 'get_activity_stats_success',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu đầu vào không hợp lệ',
                'type' => 'get_activity_stats_validation_error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy thống kê activity log không thành công',
                'type' => 'get_activity_stats_fail',
                'error' => config('app.debug') ? $e->getMessage() : 'Đã xảy ra lỗi hệ thống'
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
                    [
                        'value' => 'today',
                        'label' => 'Hôm nay',
                        'date' => now()->format('Y-m-d')
                    ],
                    [
                        'value' => 'yesterday',
                        'label' => 'Hôm qua',
                        'date' => now()->subDay()->format('Y-m-d')
                    ],
                    [
                        'value' => 'this_week',
                        'label' => 'Tuần này',
                        'date_from' => now()->startOfWeek()->format('Y-m-d'),
                        'date_to' => now()->endOfWeek()->format('Y-m-d')
                    ],
                    [
                        'value' => 'last_week',
                        'label' => 'Tuần trước',
                        'date_from' => now()->subWeek()->startOfWeek()->format('Y-m-d'),
                        'date_to' => now()->subWeek()->endOfWeek()->format('Y-m-d')
                    ],
                    [
                        'value' => 'this_month',
                        'label' => 'Tháng này',
                        'date_from' => now()->startOfMonth()->format('Y-m-d'),
                        'date_to' => now()->endOfMonth()->format('Y-m-d')
                    ],
                    [
                        'value' => 'last_month',
                        'label' => 'Tháng trước',
                        'date_from' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
                        'date_to' => now()->subMonth()->endOfMonth()->format('Y-m-d')
                    ]
                ],
                'sort_options' => [
                    [
                        'value' => 'created_at_desc',
                        'label' => 'Thời gian tạo (Mới nhất)',
                        'field' => 'created_at',
                        'direction' => 'desc'
                    ],
                    [
                        'value' => 'created_at_asc',
                        'label' => 'Thời gian tạo (Cũ nhất)',
                        'field' => 'created_at',
                        'direction' => 'asc'
                    ],
                    [
                        'value' => 'action_asc',
                        'label' => 'Hành động (A-Z)',
                        'field' => 'action',
                        'direction' => 'asc'
                    ],
                    [
                        'value' => 'action_desc',
                        'label' => 'Hành động (Z-A)',
                        'field' => 'action',
                        'direction' => 'desc'
                    ],
                    [
                        'value' => 'user_id_asc',
                        'label' => 'Người dùng (A-Z)',
                        'field' => 'user_id',
                        'direction' => 'asc'
                    ],
                    [
                        'value' => 'user_id_desc',
                        'label' => 'Người dùng (Z-A)',
                        'field' => 'user_id',
                        'direction' => 'desc'
                    ]
                ],
                'filter_options' => [
                    'page_sizes' => [10, 20, 50, 100],
                    'max_page_size' => 100,
                    'default_page_size' => 20
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
                'error' => config('app.debug') ? $e->getMessage() : 'Đã xảy ra lỗi hệ thống'
            ], 500);
        }
    }

    public function exportActivityLog(Request $request)
    {
        try {
            $input = $request->all();

            $filter = [
                'date' => $request->get('date'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'user_id' => $request->get('user_id'),
                'action' => $request->get('action'),
                'group_action' => $request->get('group_action'),
                'search' => $request->get('search'),
                'sort_field' => $request->get('sort_field', 'created_at'),
                'sort_direction' => $request->get('sort_direction', 'desc'),
            ];

            // Remove null values
            $filter = array_filter($filter, function ($value) {
                return $value !== null && $value !== '';
            });

            // Limit export to prevent performance issues
            $filter['pageSize'] = min($request->get('limit', 1000), 5000);
            $filter['page'] = 1;

            $format = $request->get('format', 'json'); // json, csv

            $this->logActivity(ActivityAction::ACCESS_VIEW, [
                'filters' => $input,
                'export_format' => $format,
                'export_limit' => $filter['pageSize']
            ], 'Xuất activity log');

            $paginator = $this->activityLogRepository->getByFilter($filter);

            // Transform data for export
            $exportData = $paginator->map(function ($log) {
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
                    'details' => $log->formatted_details
                ];
            });

            if ($format === 'csv') {
                // Generate CSV content
                $csvContent = $this->generateCsvContent($exportData);

                return response($csvContent)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="activity_log_' . now()->format('Y-m-d_H-i-s') . '.csv"');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'export_data' => $exportData,
                    'total_records' => $exportData->count(),
                    'export_format' => $format,
                    'export_limit' => $filter['pageSize'],
                    'generated_at' => now()->format('Y-m-d H:i:s'),
                    'filters_applied' => array_filter($filter, function ($key) {
                        return !in_array($key, ['pageSize', 'page']);
                    }, ARRAY_FILTER_USE_KEY)
                ],
                'message' => 'Xuất activity log thành công',
                'type' => 'export_activity_log_success',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu đầu vào không hợp lệ',
                'type' => 'export_activity_log_validation_error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xuất activity log không thành công',
                'type' => 'export_activity_log_fail',
                'error' => config('app.debug') ? $e->getMessage() : 'Đã xảy ra lỗi hệ thống'
            ], 500);
        }
    }

    private function generateCsvContent($data)
    {
        if ($data->isEmpty()) {
            return '';
        }

        $headers = [
            'ID',
            'Hành động',
            'Nhãn hành động',
            'Nhóm hành động',
            'Mô tả',
            'ID người dùng',
            'Email người dùng',
            'Tên người dùng',
            'Thời gian tạo',
            'Chi tiết'
        ];

        $csvContent = implode(',', $headers) . "\n";

        foreach ($data as $row) {
            $csvRow = [
                $row['id'],
                '"' . str_replace('"', '""', $row['action']) . '"',
                '"' . str_replace('"', '""', $row['action_label']) . '"',
                '"' . str_replace('"', '""', $row['group_action']) . '"',
                '"' . str_replace('"', '""', $row['description']) . '"',
                $row['user_id'],
                '"' . str_replace('"', '""', $row['user_email'] ?? '') . '"',
                '"' . str_replace('"', '""', $row['user_name'] ?? '') . '"',
                '"' . $row['created_at'] . '"',
                '"' . str_replace('"', '""', json_encode($row['details'])) . '"'
            ];
            $csvContent .= implode(',', $csvRow) . "\n";
        }

        return $csvContent;
    }
}
