<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BoardController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\WorkspaceController;
use App\Http\Controllers\API\PushSystemController;
use App\Http\Controllers\API\HtmlSourceController;
use App\Http\Controllers\API\UsersTrackingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/update/user', [UserController::class, 'updateCurrentUser']);
    Route::post('/change-password', [UserController::class, 'changePassword']);

    //admin change password for useer
    Route::post('/change-password-user/{id}/', [UserController::class, 'updatePassword']);

    //workspace
    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::post('/create-workspace', [WorkspaceController::class, 'store']);
    Route::get('/workspace/{id}', [WorkspaceController::class, 'show']);
    Route::post('/update-workspace/{id}', [WorkspaceController::class, 'update']);
    Route::get('/delete-workspace/{id}', [WorkspaceController::class, 'destroy']);

    //workspace-user
    Route::post('/workspace/{id}/join', [WorkspaceController::class, 'joinPublicWorkspace']);
    Route::post('/workspace/{id}/add-member', [WorkspaceController::class, 'addMember']);
    Route::post('/workspace/remove-members', [WorkspaceController::class, 'removeMember']);
    Route::get('/workspace/{id}/members', [WorkspaceController::class, 'listMembers']);

    //board
    Route::post('/create-board', [BoardController::class, 'store']); // Tạo Board
    Route::get('/board/{id}', [BoardController::class, 'show']);
    Route::get('/workspace/{id}/boards', [BoardController::class, 'index']); // Lấy danh sách Board
    Route::post('/update-board/{id}', [BoardController::class, 'update']); // Cập nhật Board
    Route::delete('/delete-board/{id}', [BoardController::class, 'destroy']); // Xóa Board

    //board-user
    Route::post('/board/{id}/join', [BoardController::class, 'joinPublicBoard']);
    Route::post('/board/{id}/add-member', [BoardController::class, 'addMember']);
    Route::post('/board/remove-members', [BoardController::class, 'removeMember']);
    Route::get('/board/{id}/members', [BoardController::class, 'listMembers']);

    Route::prefix('domain')->group(function () {
        Route::get('/', [DomainController::class, 'listDomain'])->name('domain.list');
        Route::get('/create', [DomainController::class, 'createDomain'])->name('domain.create');
        Route::get('/check', [DomainController::class, 'checkDomain'])->name('domain.check');
        Route::get('/up', [DomainController::class, 'upDomain'])->name('domain.up');
        Route::get('/search', [DomainController::class, 'searchDomain'])->name('domain.search');
        Route::get('/delete', [DomainController::class, 'deleteDomain'])->name('domain.delete');
    });

    Route::prefix('html-source')->group(function () {
        Route::get('/', [HtmlSourceController::class, 'listHtmlSource'])->name('html.source.list');
        Route::get('/{id}', [HtmlSourceController::class, 'showHtmlSource'])->name('html.source.show');
    });

    Route::prefix('users-tracking')->group(function () {
        Route::get('/', [UsersTrackingController::class, 'viewUsersTracking'])->name('users.tracking.list');
        Route::get('/get-detail-tracking', [UsersTrackingController::class, 'getDetailTracking'])->name('users.tracking.detail');
        Route::get('/get-heat-map', [UsersTrackingController::class, 'getHeatMap'])->name('users.tracking.heat.map');
        Route::get('/get-link-for-heat-map', [UsersTrackingController::class, 'getLinkForHeatMap'])->name('users.tracking.link.heat.map');
    });

    Route::prefix('team')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('team.list');
        Route::get('/create', [TeamController::class, 'create'])->name('team.create');
        Route::post('/store', [TeamController::class, 'store'])->name('team.store');
        Route::post('/update/{id}', [TeamController::class, 'update'])->name('team.update');
        Route::get('/edit/{id}', [TeamController::class, 'edit'])->name('team.edit');
        Route::get('/delete/{id}', [TeamController::class, 'destroy'])->name('team.delete');
        Route::get('/get-permission-by-team', [TeamController::class, 'getPermissionByTeam'])->name('team.get.permission');
    });

    Route::prefix('push-system')->group(function () {
        Route::get('/', [PushSystemController::class, 'listPushSystem'])->name('push.system.list');
        Route::get('/config-link/add', [PushSystemController::class, 'addConfigSystemLink'])->name('push.system.config.link');
        Route::get('/list-user-active', [PushSystemController::class, 'listUserActiveAjax'])->name('push.system.list.user.active.ajax');
        Route::get('/show-config-links', [PushSystemController::class, 'showConfigLinksPush'])->name('push.system.show.config.link');
        Route::get('/config-links', [PushSystemController::class, 'configLinksPush'])->name('push.system.edit.config.link');
    });

    Route::prefix('log-behavior')->group(function () {
        Route::get('/', [LogBehaviorController::class, 'viewLogBehavior'])->name('log.behavior.list');
        Route::get('/get-data-chart', [LogBehaviorController::class, 'getDataChartLogBehavior'])->name('log.behavior.chart');
        Route::get('/store-config-filter', [LogBehaviorController::class, 'storeConfigFilterLogBehavior'])->name('log.behavior.store.config.filter');
        Route::get('/reset-config-filter', [LogBehaviorController::class, 'resetConfigFilterLogBehavior'])->name('log.behavior.reset.config.filter');
        Route::get('/compare-date', [LogBehaviorController::class, 'compareDate'])->name('log.behavior.compare.date');
        Route::get('/save-list-app-for-check', [LogBehaviorController::class, 'saveListAppForCheck'])->name('log.behavior.save.app.in.checklist');
        Route::get('/delete-app-in-list-for-check', [LogBehaviorController::class, 'deleteAppInListForCheck'])->name('log.behavior.delete.app.in.checklist');
        Route::get('/get-activity-uid', [LogBehaviorController::class, 'getActivityUid'])->name('log.behavior.activity.uid');
    });
});
