<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use App\Enums\ActivityAction;
use Illuminate\Support\Carbon;

class ActivityLogRepository extends BaseRepository
{
    public function model()
    {
        return ActivityLog::class;
    }

    public function getByFilter($filter)
    {
        $query = $this->model->with([
            'users' => function ($query) {
                $query->select('id', 'email', 'name');
            }
        ]);

        // Date filtering
        if (isset($filter['date'])) {
            $query->whereDate('created_at', $filter['date']);
        }

        if (isset($filter['date_from']) && isset($filter['date_to'])) {
            $query->whereBetween('created_at', [
                Carbon::parse($filter['date_from'])->startOfDay(),
                Carbon::parse($filter['date_to'])->endOfDay()
            ]);
        }

        // User filtering
        if (isset($filter['user_id']) && $filter['user_id']) {
            $query->where('user_id', $filter['user_id']);
        }

        // Action filtering
        if (isset($filter['action']) && $filter['action']) {
            $query->where('action', $filter['action']);
        }

        // Group actions filtering
        if (isset($filter['group_action']) && $filter['group_action']) {
            $groupActions = $this->getGroupActions($filter['group_action']);
            if (!empty($groupActions)) {
                $query->whereIn('action', $groupActions);
            }
        }

        // Search functionality
        if (isset($filter['search']) && $filter['search']) {
            $search = $filter['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhereHas('users', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        // Sort by created_at desc by default
        $query->orderBy('created_at', 'desc');

        return $query->get()->map(function ($item) {
            $item->user_email = $item->users->email ?? null;
            $item->user_name = $item->users->name ?? null;
            $item->action_label = $this->getActionLabel($item->action);
            $item->group_action = $this->getActionGroup($item->action);
            $item->formatted_created_at = $item->created_at->format('Y-m-d H:i:s');
            unset($item->users);
            return $item;
        });
    }

    public function getActivityStats($filter = [])
    {
        $query = $this->model->query();

        // Apply same filters as getByFilter
        if (isset($filter['date'])) {
            $query->whereDate('created_at', $filter['date']);
        }

        if (isset($filter['date_from']) && isset($filter['date_to'])) {
            $query->whereBetween('created_at', [
                Carbon::parse($filter['date_from'])->startOfDay(),
                Carbon::parse($filter['date_to'])->endOfDay()
            ]);
        }

        if (isset($filter['user_id']) && $filter['user_id']) {
            $query->where('user_id', $filter['user_id']);
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
                })
        ];

        return $stats;
    }

    private function getGroupActions($group)
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
                ActivityAction::GET_PERMISSiON_BY_TEAM
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
                ActivityAction::GET_PERMISSiON_BY_TEAM
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
            ActivityAction::ACCESS_VIEW => 'Truy cập xem',
            ActivityAction::SHOW_RECORD => 'Xem bản ghi',
            ActivityAction::CREATE_RECORD => 'Tạo bản ghi',
            ActivityAction::UPDATE_RECORD => 'Cập nhật bản ghi',
            ActivityAction::DELETE_RECORD => 'Xóa bản ghi',
            ActivityAction::CREATE_SITE => 'Tạo site',
            ActivityAction::UPDATE_SITE => 'Cập nhật site',
            ActivityAction::DELETE_SITE => 'Xóa site',
            ActivityAction::ACTIVATE_SITE => 'Kích hoạt site',
            ActivityAction::DEACTIVATE_SITE => 'Vô hiệu hóa site',
            ActivityAction::CREATE_PAGE => 'Tạo trang',
            ActivityAction::UPDATE_PAGE => 'Cập nhật trang',
            ActivityAction::DELETE_PAGE => 'Xóa trang',
            ActivityAction::EXPORT_PAGE => 'Xuất trang',
            ActivityAction::CHECKIN_ATTENDANCE => 'Check-in',
            ActivityAction::CHECKOUT_ATTENDANCE => 'Check-out',
            ActivityAction::CREATE_ATTENDANCE_COMPLAINT => 'Tạo khiếu nại',
            ActivityAction::UPDATE_ATTENDANCE_COMPLAINT => 'Cập nhật khiếu nại',
            ActivityAction::CREATE_LIST => 'Tạo danh sách',
            ActivityAction::UPDATE_LIST => 'Cập nhật danh sách',
            ActivityAction::DELETE_LIST => 'Xóa danh sách',
        ];

        return $labels[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }
}
