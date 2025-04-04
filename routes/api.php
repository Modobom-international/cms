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

    //Sau route này sẽ tất cả các route sẽ được define làm phân quyền
    //Cấu trúc bắt buộc nhóm chứng năng Route -> prefix -> path -> controller -> action -> method -> name

    //workspace
    Route::prefix('workspace')->group(function () {
        Route::get('/', [WorkspaceController::class, 'index'])->name('workspace.list');
        Route::post('/create', [WorkspaceController::class, 'store'])->name('workspace.create');
        Route::get('/{id}', [WorkspaceController::class, 'show'])->name('workspace.detail');
        Route::post('/update/{id}', [WorkspaceController::class, 'update'])->name('workspace.update');
        Route::get('/delete/{id}', [WorkspaceController::class, 'destroy'])->name('workspace.destroy');

        //workspace-user
        Route::post('/{id}/join', [WorkspaceController::class, 'joinPublicWorkspace'])->name('workspace.join');
        Route::post('/{id}/add-member', [WorkspaceController::class, 'addMember'])->name('workspace.add.member');
        Route::post('/remove-members', [WorkspaceController::class, 'removeMember'])->name('workspace.remove.member');
        Route::get('/{id}/members', [WorkspaceController::class, 'listMembers'])->name('workspace.members');
    });

    //board
    Route::prefix('board')->group(function () {
        Route::post('/create', [BoardController::class, 'store'])->name('board.create');
        Route::get('/{id}', [BoardController::class, 'show'])->name('board.detail');
        Route::get('/workspace/{id}/boards', [BoardController::class, 'index'])->name('board.list.workspace');
        Route::post('/update/{id}', [BoardController::class, 'update'])->name('board.update');
        Route::delete('/delete/{id}', [BoardController::class, 'destroy'])->name('board.destroy');

        //board-user
        Route::post('/{id}/join', [BoardController::class, 'joinPublicBoard'])->name('board.join');
        Route::post('/{id}/add-member', [BoardController::class, 'addMember'])->name('board.add.member');
        Route::post('/remove-members', [BoardController::class, 'removeMember'])->name('board.remove.member');
        Route::get('/{id}/members', [BoardController::class, 'listMembers'])->name('board.members');

        //board-list
        Route::post('/create-list', [ListBoardController::class, 'store'])->name('board.list.create');
        Route::get('/list/{id}', [ListBoardController::class, 'show'])->name('board.list.detail');
        Route::get('/{boardId}/lists', [ListBoardController::class, 'index'])->name('board.list');
        Route::post('/update-list/{id}', [ListBoardController::class, 'update'])->name('board.list.update');
        Route::delete('/delete-board/{id}', [ListBoardController::class, 'destroy'])->name('board.list.destroy');
    });


    Route::prefix('card')->group(function () {
        Route::get('/list/{list}/cards', [CardController::class, 'index'])->name('card.list');
        Route::post('/create-card', [CardController::class, 'store'])->name('card.create');
        Route::post('/update-card/{card}', [CardController::class, 'update'])->name('card.update');
        Route::delete('/card/{card}', [CardController::class, 'destroy'])->name('card.destroy');
        Route::post('/card/{card}/move', [CardController::class, 'move'])->name('card.move');

        //assign-member-to-card
        Route::post('/cards/{card}/assign-member', [CardController::class, 'assignMember'])->name('card.assign.member');
        Route::delete('/cards/{card}/members/{user}', [CardController::class, 'removeMember'])->name('card.remove.member');

        Route::post('/cards/{cardId}/labels', [CardController::class, 'addLabel'])->name('card.label.add');
        Route::delete('/cards/{cardId}/labels/{labelId}', [CardController::class, 'removeLabel'])->name('card.label.remove');

        Route::post('create-label', [LabelController::class, 'store'])->name('card.label.create');
        Route::get('/label/{id}', [LabelController::class, 'show'])->name('card.label.detail');
        Route::get('labels', [LabelController::class, 'index'])->name('card.label.list');
        Route::post('/update-label/{id}', [LabelController::class, 'update'])->name('card.label.update');
        Route::delete('/delete-label/{id}', [LabelController::class, 'destroy'])->name('card.label.destroy');
    });

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

    // Page routes
    Route::post('/create-page', [PageController::class, 'create']);
    Route::post('/update-page', [PageController::class, 'update']);
    Route::get('/page/{slug}', [PageController::class, 'getPage']);
    Route::get('/pages', [PageController::class, 'getPages']);
    Route::get('/sites/{siteId}/pages', [PageController::class, 'getPagesBySite']);
    Route::post('/export-pages', [PageController::class, 'exportPage']);
});
