<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ExcludeDomainTracking;

use App\Http\Controllers\API\AttachmentController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\DueDateController;
use App\Http\Controllers\API\HtmlSourceController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BoardController;
use App\Http\Controllers\API\TeamController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\WorkspaceController;
use App\Http\Controllers\API\ListBoardController;
use App\Http\Controllers\API\CardController;
use App\Http\Controllers\API\LabelController;
use App\Http\Controllers\API\CheckListController;
use App\Http\Controllers\API\CheckListItemController;
use App\Http\Controllers\API\UsersTrackingController;
use App\Http\Controllers\API\CloudflareController;
use App\Http\Controllers\API\PageController;
use App\Http\Controllers\API\SiteController;
use App\Http\Controllers\API\PushSystemController;
use App\Http\Controllers\API\DomainController;
use App\Http\Controllers\API\ActivityLogController;
use App\Http\Controllers\API\MonitorServerController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ServerController;
use App\Http\Controllers\API\ApiKeyController;
use App\Http\Controllers\API\ImageOptimizeController;
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\CompanyIpController;
use App\Http\Controllers\API\AttendanceComplaintController;
use App\Http\Controllers\API\LeaveRequestController;
use App\Http\Controllers\API\SalaryCalculationController;
use App\Http\Controllers\API\PublicHolidayController;
use App\Http\Controllers\API\FileController;
use App\Http\Controllers\API\AppInformationController;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('api.key')->group(function () {

    //API key routes
});

Route::middleware(ExcludeDomainTracking::class)->group(function () {
    Route::post('/create-video-timeline', [UsersTrackingController::class, 'storeVideoTimeline']);
    Route::post('/collect-ai-training-data', [UsersTrackingController::class, 'storeAiTrainingData']);
    Route::post('/heartbeat', [UsersTrackingController::class, 'storeHeartbeat']);
    Route::post('/tracking-event', [UsersTrackingController::class, 'storeTrackingEvent']);
    Route::post('/check-device', [UsersTrackingController::class, 'checkDevice']);

    Route::post('/optimize', [ImageOptimizeController::class, 'optimize']);

    Route::post('/store-app-info', [AppInformationController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);

    // API Keys routes
    Route::prefix('api-keys')->group(function () {
        Route::get('/', [ApiKeyController::class, 'index'])->name('api-keys.index');
        Route::post('/', [ApiKeyController::class, 'store'])->name('api-keys.create');
        Route::put('/{id}', [ApiKeyController::class, 'update'])->name('api-keys.update');
        Route::delete('/{id}', [ApiKeyController::class, 'destroy'])->name('api-keys.delete');
        Route::post('/{id}/regenerate', [ApiKeyController::class, 'regenerate'])->name('api-keys.regenerate');
        Route::get('/{id}', [ApiKeyController::class, 'show'])->name('api-keys.show');
    });

    Route::get('/notifications', [NotificationController::class, 'getNotifications']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    Route::post('/update/user', [UserController::class, 'updateCurrentUser']);
    Route::post('/change-password', [UserController::class, 'changePassword']);

    //admin change password for user
    Route::post('/change-password-user/{id}/', [UserController::class, 'updatePassword']);
    // Page routes
    Route::post('/create-page', [PageController::class, 'create']);
    Route::post('/update-page/{pageId}', [PageController::class, 'update']);
    Route::get('/page/{pageId}', [PageController::class, 'getPage']);
    Route::get('/pages', [PageController::class, 'getPages']);
    Route::get('/sites/{siteId}/pages', [PageController::class, 'getPagesBySite']);
    Route::post('/export-pages/{pageId}', [PageController::class, 'exportPage']);
    Route::delete('/pages/{pageId}', [PageController::class, 'destroy']);

    //Sau comment này tất cả các route sẽ được tính vào phân quyền của hệ thống.

    // Cloudflare Projects API
    Route::prefix('cloudflare/projects')->group(function () {
        Route::get('/', [CloudflareController::class, 'getProjects'])->name('cloudflare.projects.index');
        Route::post('/', [CloudflareController::class, 'createProject'])->name('cloudflare.projects.create');
        Route::post('/{id}', [CloudflareController::class, 'updateProject'])->name('cloudflare.projects.update');
    });

    // Cloudflare Deployments API
    Route::prefix('cloudflare/deployments')->group(function () {
        Route::post('/', [CloudflareController::class, 'createDeployment'])->name('cloudflare.deployments.create');
        Route::post('/exports', [CloudflareController::class, 'deployExports'])->name('cloudflare.deployments.exports');
    });

    // Cloudflare Domains API
    Route::prefix('cloudflare/domains')->group(function () {
        Route::post('/', [CloudflareController::class, 'applyDomain'])->name('cloudflare.domains.create');
    });

    // Sites API
    Route::prefix('sites')->group(function () {
        Route::get('/', [SiteController::class, 'index'])->name('sites.index');
        Route::post('/', [SiteController::class, 'store'])->name('sites.create');
        Route::get('/{id}', [SiteController::class, 'show'])->name('sites.show');
        Route::post('/{id}', [SiteController::class, 'update'])->name('sites.update');
        Route::delete('/{id}', [SiteController::class, 'destroy'])->name('sites.delete');
        Route::patch('/{id}/language', [SiteController::class, 'updateLanguage'])->name('sites.language.update');

        // Site Pages API
        Route::get('/{id}/pages', [PageController::class, 'getPagesBySite'])->name('sites.pages.index');
    });

    // Pages API
    Route::prefix('pages')->group(function () {
        Route::get('/', [PageController::class, 'getPages'])->name('pages.index');
        Route::post('/', [PageController::class, 'create'])->name('pages.create');
        Route::get('/{id}', [PageController::class, 'getPage'])->name('pages.show');
        Route::post('/{id}', [PageController::class, 'update'])->name('pages.update');
        Route::delete('/{id}', [PageController::class, 'destroy'])->name('pages.delete');

        // Pages Export API
        Route::post('/{id}/exports', [PageController::class, 'exportPage'])->name('pages.exports.create');

        // Pages Tracking Script API
        Route::prefix('{id}/tracking-scripts')->group(function () {
            Route::get('/', [PageController::class, 'getTrackingScript'])->name('pages.tracking-scripts.show');
            Route::post('/', [PageController::class, 'updateTrackingScript'])->name('pages.tracking-scripts.update');
            Route::delete('/', [PageController::class, 'removeTrackingScript'])->name('pages.tracking-scripts.delete');
        });
    });

    // Workspaces API
    Route::prefix('workspaces')->group(function () {
        Route::get('/', [WorkspaceController::class, 'index'])->name('workspaces.index');
        Route::post('/', [WorkspaceController::class, 'store'])->name('workspaces.create');
        Route::get('/{id}', [WorkspaceController::class, 'show'])->name('workspaces.show');
        Route::put('/{id}', [WorkspaceController::class, 'update'])->name('workspaces.update');
        Route::delete('/{id}', [WorkspaceController::class, 'destroy'])->name('workspaces.delete');

        // Workspace Members API
        Route::prefix('{id}/members')->group(function () {
            Route::get('/', [WorkspaceController::class, 'listMembers'])->name('workspaces.members.index');
            Route::post('/', [WorkspaceController::class, 'addMember'])->name('workspaces.members.create');
            Route::post('/join', [WorkspaceController::class, 'joinPublicWorkspace'])->name('workspaces.members.join');
            Route::delete('/', [WorkspaceController::class, 'removeMember'])->name('workspaces.members.delete');
        });

        // Workspace Boards API
        Route::get('/{id}/boards', [BoardController::class, 'index'])->name('workspaces.boards.index'); // Lấy danh sách Board
    });

    // Boards API
    Route::prefix('boards')->group(function () {
        Route::post('/', [BoardController::class, 'store'])->name('boards.create');
        Route::get('/{id}', [BoardController::class, 'show'])->name('boards.show');
        Route::put('/{id}', [BoardController::class, 'update'])->name('boards.update');
        Route::delete('/{id}', [BoardController::class, 'destroy'])->name('boards.delete');

        // Board Members API
        Route::prefix('{id}/members')->group(function () {
            Route::get('/', [BoardController::class, 'listMembers'])->name('boards.members.index');
            Route::post('/', [BoardController::class, 'addMember'])->name('boards.members.create');
            Route::post('/join', [BoardController::class, 'joinPublicBoard'])->name('boards.members.join');
            Route::delete('/', [BoardController::class, 'removeMember'])->name('boards.members.delete');
        });

        // Board Lists API
        Route::get('/{id}/lists', [ListBoardController::class, 'index'])->name('boards.lists.index');
    });

    // Lists API
    Route::prefix('lists')->group(function () {
        Route::post('/', [ListBoardController::class, 'store'])->name('lists.create');
        Route::get('/{id}', [ListBoardController::class, 'show'])->name('lists.show');
        Route::post('/{id}', [ListBoardController::class, 'update'])->name('lists.update');
        Route::delete('/{id}', [ListBoardController::class, 'destroy'])->name('lists.delete');
        Route::put('/positions', [ListBoardController::class, 'updateListPositions'])->name('lists.positions.update');


        // List Cards API
        Route::prefix('{id}/cards')->group(function () {
            Route::get('/', [CardController::class, 'index'])->name('lists.cards.index');
            Route::post('/', [CardController::class, 'store'])->name('lists.cards.create');
        });
    });

    // Cards API
    Route::prefix('cards')->group(function () {
        Route::get('/{id}', [CardController::class, 'show'])->name('cards.show');
        Route::post('/{id}', [CardController::class, 'update'])->name('cards.update');
        Route::delete('/{id}', [CardController::class, 'destroy'])->name('cards.delete');
        Route::post('/{id}/move', [CardController::class, 'move'])->name('cards.move');
        Route::put('/positions', [CardController::class, 'updateCardPositions'])->name('cards.positions.update');

        Route::get('/{id}/activity', [CardController::class, 'getLogsByCard'])->name('cards.activity.index');

        // Card Members API
        Route::prefix('{id}/members')->group(function () {
            Route::get('/', [CardController::class, 'getMembers'])->name('cards.members.index');
            Route::post('/join', [CardController::class, 'join'])->name('cards.members.join');
            Route::post('/leave', [CardController::class, 'leave'])->name('cards.members.leave');
            Route::post('/', [CardController::class, 'assignMember'])->name('cards.members.create');
            Route::delete('/{user_id}', [CardController::class, 'removeMember'])->name('cards.members.delete');
        });

        // Card Labels API
        Route::prefix('{id}/labels')->group(function () {
            Route::post('/', [CardController::class, 'assignLabel'])->name('cards.labels.create');
            Route::delete('/{label_id}', [CardController::class, 'removeLabel'])->name('cards.labels.delete');
        });

        // Card Checklists API
        Route::prefix('{id}/checklists')->group(function () {
            Route::get('/', [CheckListController::class, 'index'])->name('cards.checklists.index');
            Route::post('/', [CheckListController::class, 'store'])->name('cards.checklists.create');
        });

        // Card Comments API
        Route::prefix('{id}/comments')->group(function () {
            Route::get('/', [CommentController::class, 'index'])->name('cards.comments.index');
            Route::post('/', [CommentController::class, 'store'])->name('cards.comments.create');
        });

        // Card Due Dates API
        Route::prefix('{id}/due-dates')->group(function () {
            Route::post('/', [DueDateController::class, 'store'])->name('cards.due-dates.create');
        });

        // Card Attachments API
        Route::prefix('{id}/attachments')->group(function () {
            Route::get('/', [AttachmentController::class, 'index'])->name('cards.attachments.index');
            Route::post('/', [AttachmentController::class, 'store'])->name('cards.attachments.create');
        });
    });

    // Labels API
    Route::prefix('labels')->group(function () {
        Route::get('/', [LabelController::class, 'index'])->name('labels.index');
        Route::post('/', [LabelController::class, 'store'])->name('labels.create');
        Route::get('/{id}', [LabelController::class, 'show'])->name('labels.show');
        Route::post('/{id}', [LabelController::class, 'update'])->name('labels.update');
        Route::delete('/{id}', [LabelController::class, 'destroy'])->name('labels.delete');
    });

    // Checklists API
    Route::prefix('checklists')->group(function () {
        Route::post('/{id}', [CheckListController::class, 'update'])->name('checklists.update');
        Route::delete('/{id}', [CheckListController::class, 'destroy'])->name('checklists.delete');

        // Checklist Items API
        Route::prefix('{id}/items')->group(function () {
            Route::get('/', [CheckListItemController::class, 'index'])->name('checklists.items.index');
            Route::post('/', [CheckListItemController::class, 'store'])->name('checklists.items.create');
            Route::post('/{item_id}', [CheckListItemController::class, 'update'])->name('checklists.items.update');
            Route::delete('/{item_id}', [CheckListItemController::class, 'destroy'])->name('checklists.items.delete');
            Route::post('/{item_id}/toggle', [CheckListItemController::class, 'toggle'])->name('checklists.items.toggle');
        });
    });

    // Comments API
    Route::prefix('comments')->group(function () {
        Route::post('/{id}/replies', [CommentController::class, 'reply'])->name('comments.replies.create');
        Route::post('/{id}', [CommentController::class, 'update'])->name('comments.update');
        Route::delete('/{id}', [CommentController::class, 'destroy'])->name('comments.delete');
    });

    // Due Dates API
    Route::prefix('due-dates')->group(function () {
        Route::post('/{id}', [DueDateController::class, 'update'])->name('due-dates.update');
        Route::delete('/{id}', [DueDateController::class, 'destroy'])->name('due-dates.delete');
        Route::patch('/{id}/toggle', [DueDateController::class, 'toggleComplete'])->name('due-dates.toggle');
    });

    // Attachments API
    Route::prefix('attachments')->group(function () {
        Route::post('/{id}', [AttachmentController::class, 'update'])->name('attachments.update');
        Route::delete('/{id}', [AttachmentController::class, 'destroy'])->name('attachments.delete');
    });

    Route::prefix('domains')->group(function () {
        Route::get('/', [DomainController::class, 'listDomain'])->name('domain.list');
        Route::get('/available', [DomainController::class, 'getListAvailableDomain'])->name('domain.list.available');
        Route::get('/refresh', [DomainController::class, 'refreshDomain'])->name('domain.refresh');
        Route::get('/list-url-path', [DomainController::class, 'listUrlPath'])->name('domain.list.url.path');
        Route::get('/dns-records', [DomainController::class, 'showDnsRecords'])->name('domain.dns.records');
        Route::post('/store', [DomainController::class, 'store'])->name('domain.store');
        Route::get('/get-list-domain-for-tracking', [DomainController::class, 'getListDomainForTracking'])->name('domain.get.list.for.tracking');
    });

    Route::prefix('users-tracking')->group(function () {
        Route::get('/', [UsersTrackingController::class, 'listTrackingEvent'])->name('users.tracking.list');
        Route::get('/get-current-users-active', [UsersTrackingController::class, 'getCurrentUsersActive'])->name('users.tracking.get.current.users.active');
    });

    Route::prefix('team')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('team.list');
        Route::post('/store', [TeamController::class, 'store'])->name('team.store');
        Route::post('/update/{id}', [TeamController::class, 'update'])->name('team.update');
        Route::get('/{id}', [TeamController::class, 'edit'])->name('team.edit');
        Route::delete('/delete', [TeamController::class, 'destroy'])->name('team.destroy');
        Route::get('/get-permission-by-team', [TeamController::class, 'getPermissionByTeam'])->name('team.get.permission');
        Route::get('/list-with-permission', [TeamController::class, 'listTeamWithPermission'])->name('team.list.with.permission');
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('user.list');
        Route::get('/{id}', [UserController::class, 'show'])->name('user.edit');
        Route::post('/update/{id}', [UserController::class, 'update'])->name('user.update');
        Route::delete('/delete/{id}', [UserController::class, 'destroy'])->name('user.destroy');
    });

    Route::prefix('server')->group(function () {
        Route::get('/', [ServerController::class, 'index'])->name('server.list');
        Route::post('/store', [ServerController::class, 'store'])->name('server.store');
        Route::post('/update/{id}', [ServerController::class, 'update'])->name('server.update');
        Route::delete('/delete/{id}', [ServerController::class, 'show'])->name('server.destroy');
    });

    Route::prefix('activity-log')->group(function () {
        Route::get('/', [ActivityLogController::class, 'listActivityLog'])->name('activity.log.list');
    });

    Route::prefix('monitor-server')->group(function () {
        Route::get('/detail', [MonitorServerController::class, 'detail'])->name('monitor.server.detail');
        Route::get('/store', [MonitorServerController::class, 'store'])->name('monitor.server.store');
    });

    // Company IP Management routes
    Route::prefix('company-ips')->group(function () {
        Route::get('/', [CompanyIpController::class, 'index'])->name('company.ips.index');
        Route::post('/', [CompanyIpController::class, 'store'])->name('company.ips.store');
        Route::put('/{id}', [CompanyIpController::class, 'update'])->name('company.ips.update');
        Route::delete('/{id}', [CompanyIpController::class, 'destroy'])->name('company.ips.destroy');
    });

    // Attendance routes
    Route::prefix('attendance')->group(function () {
        Route::post('/checkin', [AttendanceController::class, 'checkin'])->name('attendance.checkin');
        Route::post('/checkout', [AttendanceController::class, 'checkout'])->name('attendance.checkout');
        Route::get('/{employee_id}/today', [AttendanceController::class, 'getTodayAttendance'])->name('attendance.today');
        Route::get('/{employee_id}/by-date/{date}', [AttendanceController::class, 'getAttendanceByDate'])->name('attendance.by.date');
    });

    // Admin Attendance routes
    Route::prefix('admin/attendances')->group(function () {
        Route::get('/', [AttendanceController::class, 'getAttendanceReport'])->name('admin.attendances.report');
        Route::post('/custom', [AttendanceController::class, 'addCustomAttendance'])->name('admin.attendances.custom.store');
        Route::put('/custom/{id}', [AttendanceController::class, 'updateCustomAttendance'])->name('admin.attendances.custom.update');
    });

    // Attendance Complaints routes
    Route::prefix('attendance-complaints')->group(function () {
        Route::get('/', [AttendanceComplaintController::class, 'index'])->name('attendance.complaints.index');
        Route::post('/', [AttendanceComplaintController::class, 'store'])->name('attendance.complaints.store');
        Route::get('/{id}', [AttendanceComplaintController::class, 'show'])->name('attendance.complaints.show');
        Route::put('/{id}', [AttendanceComplaintController::class, 'update'])->name('attendance.complaints.update');
    });

    // Admin Attendance Complaints routes
    Route::prefix('admin/attendance-complaints')->group(function () {
        Route::get('/', [AttendanceComplaintController::class, 'adminIndex'])->name('admin.attendance.complaints.index');
        Route::get('/statistics', [AttendanceComplaintController::class, 'getStatistics'])->name('admin.attendance.complaints.statistics');
        Route::get('/{id}', [AttendanceComplaintController::class, 'adminShow'])->name('admin.attendance.complaints.show');
        Route::put('/{id}/status', [AttendanceComplaintController::class, 'updateStatus'])->name('admin.attendance.complaints.status.update');
        Route::post('/{id}/respond', [AttendanceComplaintController::class, 'respondToComplaint'])->name('admin.attendance.complaints.respond');
    });

    // Leave Requests routes
    Route::prefix('leave-requests')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index'])->name('leave.requests.index');
        Route::post('/', [LeaveRequestController::class, 'store'])->name('leave.requests.store');
        Route::get('/balance', [LeaveRequestController::class, 'getLeaveBalance'])->name('leave.requests.balance');
        Route::get('/active-leaves', [LeaveRequestController::class, 'getActiveLeaves'])->name('leave.requests.active');
        Route::get('/{id}', [LeaveRequestController::class, 'show'])->name('leave.requests.show');
        Route::put('/{id}', [LeaveRequestController::class, 'update'])->name('leave.requests.update');
        Route::patch('/{id}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave.requests.cancel');
    });

    // Admin Leave Requests routes
    Route::prefix('admin/leave-requests')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'adminIndex'])->name('admin.leave.requests.index');
        Route::get('/statistics', [LeaveRequestController::class, 'getStatistics'])->name('admin.leave.requests.statistics');
        Route::get('/active-leaves', [LeaveRequestController::class, 'getActiveLeaves'])->name('admin.leave.requests.active');
        Route::get('/{id}', [LeaveRequestController::class, 'adminShow'])->name('admin.leave.requests.show');
        Route::put('/{id}/status', [LeaveRequestController::class, 'updateStatus'])->name('admin.leave.requests.status.update');
    });

    // Salary calculation routes (Admin only)
    Route::prefix('admin/salary')->middleware(['role:admin'])->group(function () {
        Route::get('/calculate/{employeeId}', [SalaryCalculationController::class, 'calculateMonthlySalary']);
        Route::get('/calculate-all', [SalaryCalculationController::class, 'calculateAllSalaries']);
        Route::get('/attendance-summary/{employeeId}', [SalaryCalculationController::class, 'getAttendanceSummary']);
        Route::get('/leave-summary/{employeeId}', [SalaryCalculationController::class, 'getLeaveSummary']);
    });

    // Public holidays (read-only for employees)
    Route::prefix('holidays')->group(function () {
        Route::get('/', [PublicHolidayController::class, 'index']);
        Route::get('/upcoming', [PublicHolidayController::class, 'getUpcomingHolidays']);
        Route::get('/check', [PublicHolidayController::class, 'checkHoliday']);
        Route::get('/month', [PublicHolidayController::class, 'getHolidaysForMonth']);
        Route::get('/{id}', [PublicHolidayController::class, 'show']);
    });

    // Admin routes
    Route::prefix('admin/holidays')->middleware(['role:admin'])->group(function () {
        Route::post('/', [PublicHolidayController::class, 'store']);
        Route::put('/{id}', [PublicHolidayController::class, 'update']);
        Route::delete('/{id}', [PublicHolidayController::class, 'destroy']);
        Route::post('/generate-yearly', [PublicHolidayController::class, 'generateYearlyHolidays']);
    });

    Route::prefix('files')->group(function () {
        Route::post('/upload', [FileController::class, 'upload'])->name('files.upload');
        Route::get('/download/{id}', [FileController::class, 'download'])->name('files.download');
        Route::get('/', [FileController::class, 'list'])->name('files.list');
    });

    Route::prefix('app-information')->group(function () {
        Route::get('/', [AppInformationController::class, 'list'])->name('app.information.list');
    });
});
