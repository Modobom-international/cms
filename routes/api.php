<?php

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
use App\Http\Controllers\API\UsersTrackingController;
use App\Http\Controllers\API\CloudflareController;
use App\Http\Controllers\API\PageController;
use App\Http\Controllers\API\SiteController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/create-video-timeline', [UsersTrackingController::class, 'storeVideoTimeline']);
Route::post('/collect-ai-training-data', [UsersTrackingController::class, 'storeTrainingData']);
Route::post('/heartbeat', [UsersTrackingController::class, 'storeHeartbeat']);
Route::post('/tracking-event', [UsersTrackingController::class, 'storeTrackingEvent']);
Route::post('/check-device', [UsersTrackingController::class, 'checkDevice']);

Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/update/user', [UserController::class, 'updateCurrentUser']);
    Route::post('/change-password', [UserController::class, 'changePassword']);

    //admin change password for useer
    Route::post('/change-password-user/{id}/', [UserController::class, 'updatePassword']);

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

    //list
    Route::post('/create-list', [ListBoardController::class, 'store']); // Tạo Board
    Route::get('/list/{id}', [ListBoardController::class, 'show']);
    Route::get('/board/{boardId}/lists', [ListBoardController::class, 'index']); // Lấy danh sách Board
    Route::post('/update-list/{id}', [ListBoardController::class, 'update']); // Cập nhật Board
    Route::delete('/delete-board/{id}', [ListBoardController::class, 'destroy']); // Xóa Board

    //card
    Route::get('/list/{list}/cards', [CardController::class, 'index']); // Lấy danh sách card theo list
    Route::post('/create-card', [CardController::class, 'store']); // Tạo card mới
    Route::post('/update-card/{card}', [CardController::class, 'update']); // Cập nhật card
    Route::delete('/card/{card}', [CardController::class, 'destroy']); // Xóa card
    Route::post('/card/{card}/move', [CardController::class, 'move']); // Di chuyển card giữa các list

    //assign-member-to-card
    Route::post('/cards/{card}/assign-member', [CardController::class, 'assignMember']);
    Route::delete('/cards/{card}/members/{user}', [CardController::class, 'removeMember']);

    //label
    Route::post('create-label', [LabelController::class, 'store']); // Tạo Board
    Route::get('/label/{id}', [LabelController::class, 'show']);
    Route::get('labels', [LabelController::class, 'index']); // Lấy danh sách Board
    Route::post('/update-label/{id}', [LabelController::class, 'update']); // Cập nhật Board
    Route::delete('/delete-label/{id}', [LabelController::class, 'destroy']); // Xóa Board

    Route::post('/cards/{cardId}/labels', [CardController::class, 'addLabel']);
    Route::delete('/cards/{cardId}/labels/{labelId}', [CardController::class, 'removeLabel']);

    Route::prefix('users-tracking')->group(function () {
        Route::get('/', [UsersTrackingController::class, 'viewUsersTracking'])->name('users.tracking.list');
        Route::get('/get-detail-tracking', [UsersTrackingController::class, 'getDetailTracking'])->name('users.tracking.detail');
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

    // Site routes
    Route::prefix('sites')->group(function () {
        Route::get('/', [SiteController::class, 'index'])->name('sites.list');
        Route::post('/', [SiteController::class, 'store'])->name('sites.store');
        Route::get('/{id}', [SiteController::class, 'show'])->name('sites.show');
        Route::put('/{id}', [SiteController::class, 'update'])->name('sites.update');
        Route::delete('/{id}', [SiteController::class, 'destroy'])->name('sites.delete');
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

    // Page routes
    Route::post('/create-page', [PageController::class, 'create']);
    Route::post('/update-page', [PageController::class, 'update']);
    Route::get('/page/{slug}', [PageController::class, 'getPage']);
    Route::get('/pages', [PageController::class, 'getPages']);
    Route::get('/sites/{siteId}/pages', [PageController::class, 'getPagesBySite']);
    Route::post('/export-pages', [PageController::class, 'exportPage']);
});
