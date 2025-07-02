<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use App\Enums\ActivityAction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ActivityLogRepository extends BaseRepository
{
    public function model()
    {
        return ActivityLog::class;
    }

    public function getByFilter($filter)
    {
        // Validate filters
        $this->validateFilters($filter);

        $query = $this->model->with([
            'users:id,email,name'
        ]);

        // Date filtering - prioritize date range over single date
        if (!empty($filter['date_from']) && !empty($filter['date_to'])) {
            $query->whereBetween('created_at', [
                Carbon::parse($filter['date_from'])->startOfDay(),
                Carbon::parse($filter['date_to'])->endOfDay()
            ]);
        } elseif (!empty($filter['date'])) {
            $query->whereDate('created_at', $filter['date']);
        }

        // User filtering
        if (!empty($filter['user_id'])) {
            if (is_array($filter['user_id'])) {
                $query->whereIn('user_id', $filter['user_id']);
            } else {
                $query->where('user_id', $filter['user_id']);
            }
        }

        // Action filtering
        if (!empty($filter['action'])) {
            if (is_array($filter['action'])) {
                $query->whereIn('action', $filter['action']);
            } else {
                $query->where('action', $filter['action']);
            }
        }

        // Group actions filtering (support multiple, comma-separated)
        if (!empty($filter['group_action'])) {
            $groups = is_array($filter['group_action'])
                ? $filter['group_action']
                : array_map('trim', explode(',', $filter['group_action']));

            $allActions = [];
            foreach ($groups as $group) {
                $actions = $this->getGroupActions(strtolower($group));
                $allActions = array_merge($allActions, $actions);
            }
            if (!empty($allActions)) {
                $query->whereIn('action', array_unique($allActions));
            }
        }

        // Search functionality
        if (!empty($filter['search'])) {
            $search = $filter['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhereRaw('JSON_EXTRACT(details, "$") LIKE ?', ["%{$search}%"])
                    ->orWhereHas('users', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        // Sorting
        $sortField = $filter['sort_field'] ?? 'created_at';
        $sortDirection = $filter['sort_direction'] ?? 'desc';

        // Validate sort parameters
        $allowedSortFields = ['created_at', 'action', 'user_id'];
        $allowedSortDirections = ['asc', 'desc'];

        if (in_array($sortField, $allowedSortFields) && in_array($sortDirection, $allowedSortDirections)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $page = !empty($filter['page']) ? (int) $filter['page'] : 1;
        $perPage = !empty($filter['pageSize']) ? (int) $filter['pageSize'] : 20;

        // Limit per page to prevent performance issues
        $perPage = min($perPage, 100);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform results
        $activities = $paginator->getCollection()->map(function ($item) {
            $item->user_email = $item->users->email ?? null;
            $item->user_name = $item->users->name ?? null;
            $item->action_label = $this->getActionLabel($item->action);
            $item->group_action = $this->getActionGroup($item->action);
            $item->formatted_created_at = $item->created_at->format('Y-m-d H:i:s');
            $item->formatted_details = $this->formatDetails($item->details);
            unset($item->users);
            return $item;
        });

        // Return with pagination meta
        $paginator->setCollection($activities);
        return $paginator;
    }

    public function getActivityStats($filter = [])
    {
        // Validate filters
        $this->validateFilters($filter);

        $query = $this->model->query();

        // Apply same filters as getByFilter
        if (!empty($filter['date_from']) && !empty($filter['date_to'])) {
            $query->whereBetween('created_at', [
                Carbon::parse($filter['date_from'])->startOfDay(),
                Carbon::parse($filter['date_to'])->endOfDay()
            ]);
        } elseif (!empty($filter['date'])) {
            $query->whereDate('created_at', $filter['date']);
        }

        if (!empty($filter['user_id'])) {
            if (is_array($filter['user_id'])) {
                $query->whereIn('user_id', $filter['user_id']);
            } else {
                $query->where('user_id', $filter['user_id']);
            }
        }

        $stats = [
            'total_activities' => $query->count(),
            'actions_by_group' => $query->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->get()
                ->groupBy(function ($item) {
                    return $this->getActionGroup($item->action);
                })
                ->map(function ($group) {
                    return $group->sum('count');
                }),
            'top_users' => $query->selectRaw('user_id, COUNT(*) as count')
                ->with('users:id,email,name')
                ->groupBy('user_id')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'user_id' => $item->user_id,
                        'email' => $item->users->email ?? null,
                        'name' => $item->users->name ?? null,
                        'count' => $item->count
                    ];
                }),
            'daily_activities' => $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'count' => $item->count
                    ];
                })
        ];

        return $stats;
    }

    private function validateFilters($filter)
    {
        $rules = [
            'date' => 'nullable|date_format:Y-m-d',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'user_id' => 'nullable',
            'user_id.*' => 'exists:users,id',
            'action' => 'nullable|string',
            'group_action' => 'nullable|string',
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'sort_field' => 'nullable|string|in:created_at,action,user_id',
            'sort_direction' => 'nullable|string|in:asc,desc'
        ];

        // Handle user_id validation for both single value and array
        if (isset($filter['user_id'])) {
            if (is_array($filter['user_id'])) {
                $rules['user_id'] = 'nullable|array';
            } else {
                $rules['user_id'] = 'nullable|string|exists:users,id';
                unset($rules['user_id.*']);
            }
        }

        $validator = Validator::make($filter, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function formatDetails($details)
    {
        if (!is_array($details)) {
            return $details;
        }

        $formatted = [];
        foreach ($details as $key => $value) {
            if (is_array($value)) {
                $formatted[$key] = json_encode($value);
            } else {
                $formatted[$key] = $value;
            }
        }

        return $formatted;
    }

    private function getGroupActions($group)
    {
        // Map alternative group names to standard names
        $groupMapping = [
            'access_control' => 'general_operations',
            'cloudflare_ops' => 'cloudflare_operations',
            'domain_ops' => 'domain_operations',
        ];

        $group = $groupMapping[$group] ?? $group;

        $actionGroups = [
            'site_management' => [
                ActivityAction::CREATE_SITE,
                ActivityAction::UPDATE_SITE,
                ActivityAction::DELETE_SITE,
                ActivityAction::ACTIVATE_SITE,
                ActivityAction::DEACTIVATE_SITE,
                ActivityAction::UPDATE_SITE_LANGUAGE,
                ActivityAction::UPDATE_SITE_PLATFORM
            ],
            'page_management' => [
                ActivityAction::CREATE_PAGE,
                ActivityAction::UPDATE_PAGE,
                ActivityAction::DELETE_PAGE,
                ActivityAction::EXPORT_PAGE,
                ActivityAction::UPDATE_TRACKING_SCRIPT,
                ActivityAction::REMOVE_TRACKING_SCRIPT,
                ActivityAction::GET_TRACKING_SCRIPT,
                ActivityAction::CANCEL_EXPORT,
                ActivityAction::CREATE_PAGE_EXPORTS,
                ActivityAction::CREATE_PAGES,
                ActivityAction::UPDATE_PAGES
            ],
            'attendance_management' => [
                ActivityAction::CHECKIN_ATTENDANCE,
                ActivityAction::CHECKOUT_ATTENDANCE,
                ActivityAction::GET_ATTENDANCE,
                ActivityAction::GET_ATTENDANCE_REPORT,
                ActivityAction::ADD_CUSTOM_ATTENDANCE,
                ActivityAction::UPDATE_CUSTOM_ATTENDANCE
            ],
            'attendance_complaints' => [
                ActivityAction::CREATE_ATTENDANCE_COMPLAINT,
                ActivityAction::UPDATE_ATTENDANCE_COMPLAINT,
                ActivityAction::GET_ATTENDANCE_COMPLAINTS,
                ActivityAction::REVIEW_ATTENDANCE_COMPLAINT,
                ActivityAction::RESPOND_TO_ATTENDANCE_COMPLAINT,
                ActivityAction::GET_ATTENDANCE_COMPLAINT_STATS
            ],
            'board_management' => [
                ActivityAction::CREATE_LIST,
                ActivityAction::UPDATE_LIST,
                ActivityAction::DELETE_LIST,
                ActivityAction::UPDATE_LIST_POSITIONS,
                ActivityAction::GET_BOARD_LISTS,
                ActivityAction::ADD_BOARD_MEMBER,
                ActivityAction::REMOVE_BOARD_MEMBER,
                ActivityAction::GET_BOARD_MEMBERS
            ],
            'cloudflare_operations' => [
                ActivityAction::CREATE_PROJECT_CLOUDFLARE_PAGE,
                ActivityAction::UPDATE_PROJECT_CLOUDFLARE_PAGE,
                ActivityAction::CREATE_DEPLOY_CLOUDFLARE_PAGE,
                ActivityAction::APPLY_PAGE_DOMAIN_CLOUDFLARE_PAGE,
                ActivityAction::DEPLOY_EXPORT_CLOUDFLARE_PAGE
            ],
            'domain_operations' => [
                ActivityAction::REFRESH_LIST_DOMAIN,
                ActivityAction::GET_LIST_PATH_BY_DOMAIN
            ],
            'general_operations' => [
                ActivityAction::ACCESS_VIEW,
                ActivityAction::SHOW_RECORD,
                ActivityAction::CREATE_RECORD,
                ActivityAction::UPDATE_RECORD,
                ActivityAction::DELETE_RECORD,
                ActivityAction::GET_PERMISSiON_BY_TEAM,
                ActivityAction::DETAIL_MONITOR_SERVER
            ]
        ];

        return $actionGroups[$group] ?? [];
    }

    private function getActionGroup($action)
    {
        $actionGroups = [
            'site_management' => [
                ActivityAction::CREATE_SITE,
                ActivityAction::UPDATE_SITE,
                ActivityAction::DELETE_SITE,
                ActivityAction::ACTIVATE_SITE,
                ActivityAction::DEACTIVATE_SITE,
                ActivityAction::UPDATE_SITE_LANGUAGE,
                ActivityAction::UPDATE_SITE_PLATFORM
            ],
            'page_management' => [
                ActivityAction::CREATE_PAGE,
                ActivityAction::UPDATE_PAGE,
                ActivityAction::DELETE_PAGE,
                ActivityAction::EXPORT_PAGE,
                ActivityAction::UPDATE_TRACKING_SCRIPT,
                ActivityAction::REMOVE_TRACKING_SCRIPT,
                ActivityAction::GET_TRACKING_SCRIPT,
                ActivityAction::CANCEL_EXPORT,
                ActivityAction::CREATE_PAGE_EXPORTS,
                ActivityAction::CREATE_PAGES,
                ActivityAction::UPDATE_PAGES
            ],
            'attendance_management' => [
                ActivityAction::CHECKIN_ATTENDANCE,
                ActivityAction::CHECKOUT_ATTENDANCE,
                ActivityAction::GET_ATTENDANCE,
                ActivityAction::GET_ATTENDANCE_REPORT,
                ActivityAction::ADD_CUSTOM_ATTENDANCE,
                ActivityAction::UPDATE_CUSTOM_ATTENDANCE
            ],
            'attendance_complaints' => [
                ActivityAction::CREATE_ATTENDANCE_COMPLAINT,
                ActivityAction::UPDATE_ATTENDANCE_COMPLAINT,
                ActivityAction::GET_ATTENDANCE_COMPLAINTS,
                ActivityAction::REVIEW_ATTENDANCE_COMPLAINT,
                ActivityAction::RESPOND_TO_ATTENDANCE_COMPLAINT,
                ActivityAction::GET_ATTENDANCE_COMPLAINT_STATS
            ],
            'board_management' => [
                ActivityAction::CREATE_LIST,
                ActivityAction::UPDATE_LIST,
                ActivityAction::DELETE_LIST,
                ActivityAction::UPDATE_LIST_POSITIONS,
                ActivityAction::GET_BOARD_LISTS,
                ActivityAction::ADD_BOARD_MEMBER,
                ActivityAction::REMOVE_BOARD_MEMBER,
                ActivityAction::GET_BOARD_MEMBERS
            ],
            'cloudflare_operations' => [
                ActivityAction::CREATE_PROJECT_CLOUDFLARE_PAGE,
                ActivityAction::UPDATE_PROJECT_CLOUDFLARE_PAGE,
                ActivityAction::CREATE_DEPLOY_CLOUDFLARE_PAGE,
                ActivityAction::APPLY_PAGE_DOMAIN_CLOUDFLARE_PAGE,
                ActivityAction::DEPLOY_EXPORT_CLOUDFLARE_PAGE
            ],
            'domain_operations' => [
                ActivityAction::REFRESH_LIST_DOMAIN,
                ActivityAction::GET_LIST_PATH_BY_DOMAIN
            ],
            'general_operations' => [
                ActivityAction::ACCESS_VIEW,
                ActivityAction::SHOW_RECORD,
                ActivityAction::CREATE_RECORD,
                ActivityAction::UPDATE_RECORD,
                ActivityAction::DELETE_RECORD,
                ActivityAction::GET_PERMISSiON_BY_TEAM,
                ActivityAction::DETAIL_MONITOR_SERVER
            ]
        ];

        foreach ($actionGroups as $group => $actions) {
            if (in_array($action, $actions)) {
                return $group;
            }
        }

        return 'other';
    }

    private function getActionLabel($action)
    {
        $labels = [
                // General Operations
            ActivityAction::ACCESS_VIEW => 'Truy cập xem',
            ActivityAction::SHOW_RECORD => 'Xem bản ghi',
            ActivityAction::CREATE_RECORD => 'Tạo bản ghi',
            ActivityAction::UPDATE_RECORD => 'Cập nhật bản ghi',
            ActivityAction::DELETE_RECORD => 'Xóa bản ghi',
            ActivityAction::GET_PERMISSiON_BY_TEAM => 'Lấy quyền theo nhóm',
            ActivityAction::DETAIL_MONITOR_SERVER => 'Xem chi tiết máy chủ',

                // Site Management
            ActivityAction::CREATE_SITE => 'Tạo site',
            ActivityAction::UPDATE_SITE => 'Cập nhật site',
            ActivityAction::DELETE_SITE => 'Xóa site',
            ActivityAction::ACTIVATE_SITE => 'Kích hoạt site',
            ActivityAction::DEACTIVATE_SITE => 'Vô hiệu hóa site',
            ActivityAction::UPDATE_SITE_LANGUAGE => 'Cập nhật ngôn ngữ site',
            ActivityAction::UPDATE_SITE_PLATFORM => 'Cập nhật nền tảng site',

                // Page Management
            ActivityAction::CREATE_PAGE => 'Tạo trang',
            ActivityAction::UPDATE_PAGE => 'Cập nhật trang',
            ActivityAction::DELETE_PAGE => 'Xóa trang',
            ActivityAction::EXPORT_PAGE => 'Xuất trang',
            ActivityAction::UPDATE_TRACKING_SCRIPT => 'Cập nhật script tracking',
            ActivityAction::REMOVE_TRACKING_SCRIPT => 'Xóa script tracking',
            ActivityAction::GET_TRACKING_SCRIPT => 'Lấy script tracking',
            ActivityAction::CANCEL_EXPORT => 'Hủy xuất',
            ActivityAction::CREATE_PAGE_EXPORTS => 'Tạo xuất trang',
            ActivityAction::CREATE_PAGES => 'Tạo nhiều trang',
            ActivityAction::UPDATE_PAGES => 'Cập nhật nhiều trang',

                // Attendance Management
            ActivityAction::CHECKIN_ATTENDANCE => 'Check-in',
            ActivityAction::CHECKOUT_ATTENDANCE => 'Check-out',
            ActivityAction::GET_ATTENDANCE => 'Xem chấm công',
            ActivityAction::GET_ATTENDANCE_REPORT => 'Báo cáo chấm công',
            ActivityAction::ADD_CUSTOM_ATTENDANCE => 'Thêm chấm công thủ công',
            ActivityAction::UPDATE_CUSTOM_ATTENDANCE => 'Cập nhật chấm công thủ công',

                // Attendance Complaints
            ActivityAction::CREATE_ATTENDANCE_COMPLAINT => 'Tạo khiếu nại chấm công',
            ActivityAction::UPDATE_ATTENDANCE_COMPLAINT => 'Cập nhật khiếu nại chấm công',
            ActivityAction::GET_ATTENDANCE_COMPLAINTS => 'Xem khiếu nại chấm công',
            ActivityAction::REVIEW_ATTENDANCE_COMPLAINT => 'Xem xét khiếu nại chấm công',
            ActivityAction::RESPOND_TO_ATTENDANCE_COMPLAINT => 'Phản hồi khiếu nại chấm công',
            ActivityAction::GET_ATTENDANCE_COMPLAINT_STATS => 'Thống kê khiếu nại chấm công',

                // Board Management
            ActivityAction::CREATE_LIST => 'Tạo danh sách',
            ActivityAction::UPDATE_LIST => 'Cập nhật danh sách',
            ActivityAction::DELETE_LIST => 'Xóa danh sách',
            ActivityAction::UPDATE_LIST_POSITIONS => 'Cập nhật vị trí danh sách',
            ActivityAction::GET_BOARD_LISTS => 'Lấy danh sách bảng',
            ActivityAction::ADD_BOARD_MEMBER => 'Thêm thành viên bảng',
            ActivityAction::REMOVE_BOARD_MEMBER => 'Xóa thành viên bảng',
            ActivityAction::GET_BOARD_MEMBERS => 'Lấy thành viên bảng',

                // Cloudflare Operations
            ActivityAction::CREATE_PROJECT_CLOUDFLARE_PAGE => 'Tạo dự án Cloudflare',
            ActivityAction::UPDATE_PROJECT_CLOUDFLARE_PAGE => 'Cập nhật dự án Cloudflare',
            ActivityAction::CREATE_DEPLOY_CLOUDFLARE_PAGE => 'Tạo triển khai Cloudflare',
            ActivityAction::APPLY_PAGE_DOMAIN_CLOUDFLARE_PAGE => 'Áp dụng domain Cloudflare',
            ActivityAction::DEPLOY_EXPORT_CLOUDFLARE_PAGE => 'Triển khai xuất Cloudflare',

                // Domain Operations
            ActivityAction::REFRESH_LIST_DOMAIN => 'Làm mới danh sách domain',
            ActivityAction::GET_LIST_PATH_BY_DOMAIN => 'Lấy đường dẫn theo domain',
        ];

        return $labels[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }
}
