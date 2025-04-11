<?php

use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\HtmlSourceController;
use Illuminate\Http\Request;
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
use App\Http\Middleware\ExcludeDomainTracking;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

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

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/update/user', [UserController::class, 'updateCurrentUser']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
    //admin change password for user
    Route::post('/change-password-user/{id}/', [UserController::class, 'updatePassword']);

    // Page routes
    Route::post('/create-page', [PageController::class, 'create']);
    Route::post('/update-page', [PageController::class, 'update']);
    Route::get('/page/{slug}', [PageController::class, 'getPage']);
    Route::get('/pages', [PageController::class, 'getPages']);
    Route::get('/sites/{siteId}/pages', [PageController::class, 'getPagesBySite']);
    Route::post('/export-pages', [PageController::class, 'exportPage']);

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
    Route::get('/list/{list}/cards', [CardController::class, 'index']); // Lấy danh sách card theo list
    Route::prefix('card')->group(function () {
        Route::post('/create', [CardController::class, 'store']); // Tạo card mới
        Route::post('/update/{card}', [CardController::class, 'update']); // Cập nhật card
        Route::delete('/delete/{card}', [CardController::class, 'destroy']); // Xóa card
        Route::post('/{card}/move', [CardController::class, 'move']); // Di chuyển card giữa các list
    });

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
    Route::post('/cards/{cardId}/labels', [CardController::class, 'addLabel']);
    Route::delete('/cards/{cardId}/labels/{labelId}', [CardController::class, 'removeLabel']);

    // checkList
    Route::get('/cards/{card}/checklists', [ChecklistController::class, 'index']);
    Route::post('/cards/{card}/checklist/store', [ChecklistController::class, 'store']);
    Route::post('/checklist/update/{id}', [ChecklistController::class, 'update']);
    Route::delete('/checklist/delete/{id}', [ChecklistController::class, 'destroy']);

    //checkListItem
    Route::prefix('/checklist')->group(function () {
        Route::get('/{checklist}/checklist-item/list', [ChecklistItemController::class, 'index']);
        Route::post('/{checklist}/checklist-item/store', [CheckListItemController::class, 'store']); // Chi tiết 1 checklist
        Route::post('/{checklist}/checklist-item/update/{item}', [CheckListItemController::class, 'update']); // Cập nhật checklist
        Route::delete('/{checklist}/checklist-item/delete/{item}', [CheckListItemController::class, 'destroy']); // Xoá checklist
        Route::post('/{checklist}/checklist-item/toggle/{item}', [ChecklistItemController::class, 'toggle']); // Check/Uncheck
    });
    
    //comment
    Route::prefix('/card/{card}/comment')->group(function () {
        Route::get('/list', [CommentController::class, 'index']); // Lấy tất cả comment (kèm replies)
        Route::post('/create', [CommentController::class, 'store']); // Tạo comment hoặc reply
        Route::post('{comment}/reply', [CommentController::class, 'reply']); // Tạo comment hoặc reply
        Route::post('/update/{comment}', [CommentController::class, 'update']); // Cập nhật comment
        Route::delete('/delete/{comment}', [CommentController::class, 'destroy']); // Xóa comment
    });

    Route::prefix('domain')->group(function () {
        Route::get('/', [DomainController::class, 'listDomain'])->name('domain.list');
    });

    Route::prefix('html-source')->group(function () {
        Route::get('/', [HtmlSourceController::class, 'listHtmlSource'])->name('html.source.list');
        Route::get('/{id}', [HtmlSourceController::class, 'showHtmlSource'])->name('html.source.show');
    });

    Route::prefix('users-tracking')->group(function () {
        Route::get('/', [UsersTrackingController::class, 'listTrackingEvent'])->name('users.tracking.list');
        Route::get('/get-detail-tracking', [UsersTrackingController::class, 'getDetailTracking'])->name('users.tracking.detail');
    });

    Route::prefix('team')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('team.list');
        Route::get('/create', [TeamController::class, 'create'])->name('team.create');
        Route::post('/store', [TeamController::class, 'store'])->name('team.store');
        Route::post('/update/{id}', [TeamController::class, 'update'])->name('team.update');
        Route::get('/edit/{id}', [TeamController::class, 'edit'])->name('team.edit');
        Route::get('/delete/{id}', [TeamController::class, 'destroy'])->name('team.destroy');
        Route::get('/get-permission-by-team', [TeamController::class, 'getPermissionByTeam'])->name('team.get.permission');
    });

    // Site routes
    Route::prefix('sites')->group(function () {
        Route::get('/', [SiteController::class, 'index'])->name('sites.list');
        Route::post('/', [SiteController::class, 'store'])->name('sites.store');
        Route::get('/{id}', [SiteController::class, 'show'])->name('sites.show');
        Route::put('/{id}', [SiteController::class, 'update'])->name('sites.update');
        Route::delete('/{id}', [SiteController::class, 'destroy'])->name('sites.destroy');
    });

    // Cloudflare Pages API routes
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
