<?php

use App\Http\Controllers\API\AttachmentController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\DueDateController;
use App\Http\Controllers\API\HtmlSourceController;
use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\API\NotificationController;
use App\Http\Middleware\ExcludeDomainTracking;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(ExcludeDomainTracking::class)->group(function () {
    Route::post('/create-video-timeline', [UsersTrackingController::class, 'storeVideoTimeline']);
    Route::post('/collect-ai-training-data', [UsersTrackingController::class, 'storeAiTrainingData']);
    Route::post('/heartbeat', [UsersTrackingController::class, 'storeHeartbeat']);
    Route::post('/tracking-event', [UsersTrackingController::class, 'storeTrackingEvent']);
    Route::post('/check-device', [UsersTrackingController::class, 'checkDevice']);

    Route::post('/save-html-source', [HtmlSourceController::class, 'storeHtmlSource']);
    Route::post('/push-system', [PushSystemController::class, 'storePushSystem']);
    Route::post('/get-push-system-config', [PushSystemController::class, 'storePushSystemSetting']);
    Route::post('/add-user-active-push-system', [PushSystemController::class, 'storePushSystemUserActive']);
    Route::post('/push-system/save-config-links', [PushSystemController::class, 'storePushSystemConfig']);
    Route::post('/save-status-link', [PushSystemController::class, 'storeStatusLink']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);

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

    //workspace
    Route::prefix('/workspace')->group(function () {
        Route::get('/list', [WorkspaceController::class, 'index']);
        Route::post('/create', [WorkspaceController::class, 'store']);
        Route::get('/{id}', [WorkspaceController::class, 'show']);
        Route::post('/update/{id}', [WorkspaceController::class, 'update']);
        Route::get('/delete/{id}', [WorkspaceController::class, 'destroy']);

        //workspace-user
        Route::post('/{id}/join', [WorkspaceController::class, 'joinPublicWorkspace']);
        Route::post('/{id}/add-member', [WorkspaceController::class, 'addMember']);
        Route::post('/remove-members', [WorkspaceController::class, 'removeMember']);
        Route::get('/{id}/members', [WorkspaceController::class, 'listMembers']);
    });

    //board
    Route::get('/workspace/{id}/boards', [BoardController::class, 'index']); // Lấy danh sách Board
    Route::prefix('board')->group(function () {
        Route::post('/create', [BoardController::class, 'store']); // Tạo Board
        Route::get('/{id}', [BoardController::class, 'show']);
        Route::post('/update/{id}', [BoardController::class, 'update']); // Cập nhật Board
        Route::get('/delete/{id}', [BoardController::class, 'destroy']); // Xóa Board
        //board-user
        Route::post('/board/{id}/join', [BoardController::class, 'joinPublicBoard']);
        Route::post('/board/{id}/add-member', [BoardController::class, 'addMember']);
        Route::post('/board/remove-members', [BoardController::class, 'removeMember']);
        Route::get('/board/{id}/members', [BoardController::class, 'listMembers']);
    });

    //list
    Route::get('/board/{boardId}/lists', [ListBoardController::class, 'index']); // Lấy danh sách list
    Route::prefix('list')->group(function () {
        Route::post('/create', [ListBoardController::class, 'store']); // Tạo list
        Route::get('/{id}', [ListBoardController::class, 'show']);
        Route::post('/update/{id}', [ListBoardController::class, 'update']); // Cập nhật list
        Route::get('/delete/{id}', [ListBoardController::class, 'destroy']); // Xóa list
    });

    //card
    Route::prefix('/list/{list}')->group(function () {
        Route::get('/cards', [CardController::class, 'index']); // Lấy danh sách card theo list
        Route::post('/card/create', [CardController::class, 'store']); // Tạo card mới
    });
    Route::post('/card/update/{card}', [CardController::class, 'update']); // Cập nhật card
    Route::delete('/card/delete/{card}', [CardController::class, 'destroy']); // Xóa card
    Route::post('/card/{card}/move', [CardController::class, 'move']); // Di chuyển card giữa các list

    //list log activity
    Route::get('/cards/{cardId}/logs', [CardController::class, 'getLogsByCard']);

    //label
    Route::prefix('/label')->group(function () {
        Route::post('/create', [LabelController::class, 'store']); // Tạo label
        Route::get('/{id}', [LabelController::class, 'show']);
        Route::get('/list', [LabelController::class, 'index']); // Lấy danh sách label
        Route::post('/update/{id}', [LabelController::class, 'update']); // Cập nhật label
        Route::delete('/delete/{id}', [LabelController::class, 'destroy']); // Xóa label
    });

    // member-card
    Route::post('/cards/{card}/join', [CardController::class, 'join']);
    Route::post('/cards/{card}/leave', [CardController::class, 'leave']);
    Route::post('/cards/{card}/assign-members', [CardController::class, 'assignMember']);
    Route::delete('/cards/{card}/members/{user}', [CardController::class, 'removeMember']);

    //assign-label-to-card
    Route::post('/card/{cardId}/assign-label', [CardController::class, 'assignLabel']);
    Route::delete('/card/{cardId}/label/{labelId}', [CardController::class, 'removeLabel']);

    // checkList
    Route::get('/cards/{card}/checklists', [CheckListController::class, 'index']);
    Route::post('/cards/{card}/checklist/store', [CheckListController::class, 'store']);
    Route::post('/checklist/update/{id}', [CheckListController::class, 'update']);
    Route::delete('/checklist/delete/{id}', [CheckListController::class, 'destroy']);

    //checkListItem
    Route::prefix('/checklist')->group(function () {
        Route::get('/{checklist}/checklist-item/list', [CheckListItemController::class, 'index']);
        Route::post('/{checklist}/checklist-item/store', [CheckListItemController::class, 'store']); // Chi tiết 1 checklist
        Route::post('/{checklist}/checklist-item/update/{item}', [CheckListItemController::class, 'update']); // Cập nhật checklist
        Route::delete('/{checklist}/checklist-item/delete/{item}', [CheckListItemController::class, 'destroy']); // Xoá checklist
        Route::post('/{checklist}/checklist-item/toggle/{item}', [ChecklistItemController::class, 'toggle']); // Check/Uncheck
    });

    //comment
    Route::prefix('/card/{card}/comment')->group(function () {
        Route::get('/list', [CommentController::class, 'index']); // Lấy tất cả comment (kèm replies)
        Route::post('/create', [CommentController::class, 'store']); // Tạo comment hoặc reply
    });
    Route::post('/comment/{comment}/reply', [CommentController::class, 'reply']); // Tạo comment hoặc reply
    Route::post('/comment/update/{comment}', [CommentController::class, 'update']); // Cập nhật comment
    Route::delete('/comment/delete/{comment}', [CommentController::class, 'destroy']); // Xóa comment

    //due date
    Route::prefix('card/{card}/due-date')->group(function () {
        Route::post('/create', [DueDateController::class, 'store']);     // Tạo hoặc cập nhật due date
    });
    Route::put('/due-date/update/{id}', [DueDateController::class, 'update']);     // Sửa due date
    Route::delete('/due-date/delete/{id}', [DueDateController::class, 'destroy']); // Xoá due date
    Route::patch('/due-date/toggle-complete/{id}', [DueDateController::class, 'toggleComplete']);

    //attachment
    Route::get('/card/{cardId}/attachments', [AttachmentController::class, 'index']);
    Route::post('/card/{cardId}/attachment/store', [AttachmentController::class, 'store']);
    Route::put('/attachment/{id}', [AttachmentController::class, 'update']);
    Route::delete('/attachment/{id}', [AttachmentController::class, 'destroy']);

    Route::prefix('domains')->group(function () {
        Route::get('/', [DomainController::class, 'listDomain'])->name('domain.list');
        Route::get('/available', [DomainController::class, 'getListAvailableDomain'])->name('domain.list.available');
        Route::get('/refresh', [DomainController::class, 'refreshDomain'])->name('domain.refresh');
        Route::get('/list-url-path', [DomainController::class, 'listUrlPath'])->name('domain.list.url.path');
        Route::post('/store', [DomainController::class, 'store'])->name('domain.store');
    });

    Route::prefix('html-source')->group(function () {
        Route::get('/', [HtmlSourceController::class, 'listHtmlSource'])->name('html.source.list');
        Route::get('/{id}', [HtmlSourceController::class, 'showHtmlSource'])->name('html.source.show');
    });

    Route::prefix('users-tracking')->group(function () {
        Route::get('/', [UsersTrackingController::class, 'listTrackingEvent'])->name('users.tracking.list');
        Route::get('/get-detail-tracking', [UsersTrackingController::class, 'getDetailTracking'])->name('users.tracking.detail');
        Route::get('/get-current-users-active', [UsersTrackingController::class, 'getCurrentUsersActive'])->name('users.tracking.get.current.users.active');
    });

    Route::prefix('team')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('team.list');
        Route::post('/store', [TeamController::class, 'store'])->name('team.store');
        Route::post('/update/{id}', [TeamController::class, 'update'])->name('team.update');
        Route::get('/delete/{id}', [TeamController::class, 'destroy'])->name('team.destroy');
        Route::get('/get-permission-by-team', [TeamController::class, 'getPermissionByTeam'])->name('team.get.permission');
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('user.list');
        Route::get('/{id}', [UserController::class, 'show'])->name('user.edit');
        Route::post('/update/{id}', [UserController::class, 'update'])->name('user.update');
        Route::delete('/delete/{id}', [UserController::class, 'destroy'])->name('user.destroy');
    });

    Route::prefix('sites')->group(function () {
        Route::get('/', [SiteController::class, 'index'])->name('sites.list');
        Route::post('/', [SiteController::class, 'store'])->name('sites.store');
        Route::get('/{id}', [SiteController::class, 'show'])->name('sites.show');
        Route::put('/{id}', [SiteController::class, 'update'])->name('sites.update');
        Route::delete('/{id}', [SiteController::class, 'destroy'])->name('sites.destroy');
    });

    Route::prefix('cloudflare')->group(function () {
        Route::get('/projects', [CloudflareController::class, 'getProjects'])->name('cloudflare.get.projects');
        Route::post('/project/create', [CloudflareController::class, 'createProject'])->name('cloudflare.create.project');
        Route::post('/project/update', [CloudflareController::class, 'updateProject'])->name('cloudflare.update.project');
        Route::post('/deploy', [CloudflareController::class, 'createDeployment'])->name('cloudflare.create.deployment');
        Route::post('/domain/apply', [CloudflareController::class, 'applyDomain'])->name('cloudflare.apply.domain');
        Route::post('/deploy-exports', [CloudflareController::class, 'deployExports'])->name('cloudflare.deploy.exports');
    });

    Route::prefix('push-system')->group(function () {
        Route::get('/', [PushSystemController::class, 'listPushSystem'])->name('push.system.list');
        Route::get('/config/store', [PushSystemController::class, 'storePushSystemConfigByAdmin'])->name('push.system.config.store');
        Route::get('/user-active/list', [PushSystemController::class, 'listPushSystemUserActive'])->name('push.system.user.active.list');
        Route::post('/config/update/{id}', [PushSystemController::class, 'updatePushSystemConfig'])->name('push.system.config.update');
        Route::get('/count-current-push', [PushSystemController::class, 'getCountCurrentPush'])->name('push.system.count.current.push');
    });

    Route::prefix('html-source')->group(function () {
        Route::get('/', [HtmlSourceController::class, 'listHtmlSource'])->name('html.source.list');
        Route::get('/{id}', [HtmlSourceController::class, 'showHtmlSource'])->name('html.source.show');
    });

    Route::prefix('activity-log')->group(function () {
        Route::get('/', [ActivityLogController::class, 'listActivityLog'])->name('activity.log.list');
    });
});
