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
use App\Http\Controllers\API\ServerController;
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

    // Cloudflare Projects API
    Route::prefix('cloudflare/projects')->group(function () {
        Route::get('/', [CloudflareController::class, 'getProjects'])->name('cloudflare.projects.index');
        Route::post('/', [CloudflareController::class, 'createProject'])->name('cloudflare.projects.create');
        Route::put('/{id}', [CloudflareController::class, 'updateProject'])->name('cloudflare.projects.update');
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
        Route::put('/{id}', [SiteController::class, 'update'])->name('sites.update');
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
        Route::put('/{id}', [PageController::class, 'update'])->name('pages.update');
        Route::delete('/{id}', [PageController::class, 'destroy'])->name('pages.delete');

        // Pages Export API
        Route::post('/{id}/exports', [PageController::class, 'exportPage'])->name('pages.exports.create');

        // Pages Tracking Script API
        Route::prefix('{id}/tracking-scripts')->group(function () {
            Route::get('/', [PageController::class, 'getTrackingScript'])->name('pages.tracking-scripts.show');
            Route::put('/', [PageController::class, 'updateTrackingScript'])->name('pages.tracking-scripts.update');
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
        Route::put('/{id}', [ListBoardController::class, 'update'])->name('lists.update');
        Route::delete('/{id}', [ListBoardController::class, 'destroy'])->name('lists.delete');
        Route::put('/positions', [ListBoardController::class, 'updatePositions'])->name('lists.positions.update');

        // List Cards API
        Route::prefix('{id}/cards')->group(function () {
            Route::get('/', [CardController::class, 'index'])->name('lists.cards.index');
            Route::post('/', [CardController::class, 'store'])->name('lists.cards.create');
        });
    });

    // Cards API
    Route::prefix('cards')->group(function () {
        Route::get('/{id}', [CardController::class, 'show'])->name('cards.show');
        Route::put('/{id}', [CardController::class, 'update'])->name('cards.update');
        Route::delete('/{id}', [CardController::class, 'destroy'])->name('cards.delete');
        Route::post('/{id}/move', [CardController::class, 'move'])->name('cards.move');
        Route::put('/positions', [CardController::class, 'updatePositions'])->name('cards.positions.update');
        Route::get('/{id}/activity', [CardController::class, 'getLogsByCard'])->name('cards.activity.index');

        // Card Members API
        Route::prefix('{id}/members')->group(function () {
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
        Route::put('/{id}', [LabelController::class, 'update'])->name('labels.update');
        Route::delete('/{id}', [LabelController::class, 'destroy'])->name('labels.delete');
    });

    // Checklists API
    Route::prefix('checklists')->group(function () {
        Route::put('/{id}', [CheckListController::class, 'update'])->name('checklists.update');
        Route::delete('/{id}', [CheckListController::class, 'destroy'])->name('checklists.delete');

        // Checklist Items API
        Route::prefix('{id}/items')->group(function () {
            Route::get('/', [CheckListItemController::class, 'index'])->name('checklists.items.index');
            Route::post('/', [CheckListItemController::class, 'store'])->name('checklists.items.create');
            Route::put('/{item_id}', [CheckListItemController::class, 'update'])->name('checklists.items.update');
            Route::delete('/{item_id}', [CheckListItemController::class, 'destroy'])->name('checklists.items.delete');
            Route::post('/{item_id}/toggle', [CheckListItemController::class, 'toggle'])->name('checklists.items.toggle');
        });
    });

    // Comments API
    Route::prefix('comments')->group(function () {
        Route::post('/{id}/replies', [CommentController::class, 'reply'])->name('comments.replies.create');
        Route::put('/{id}', [CommentController::class, 'update'])->name('comments.update');
        Route::delete('/{id}', [CommentController::class, 'destroy'])->name('comments.delete');
    });

    // Due Dates API
    Route::prefix('due-dates')->group(function () {
        Route::put('/{id}', [DueDateController::class, 'update'])->name('due-dates.update');
        Route::delete('/{id}', [DueDateController::class, 'destroy'])->name('due-dates.delete');
        Route::patch('/{id}/toggle', [DueDateController::class, 'toggleComplete'])->name('due-dates.toggle');
    });

    // Attachments API
    Route::prefix('attachments')->group(function () {
        Route::put('/{id}', [AttachmentController::class, 'update'])->name('attachments.update');
        Route::delete('/{id}', [AttachmentController::class, 'destroy'])->name('attachments.delete');
    });

    Route::prefix('domains')->group(function () {
        Route::get('/', [DomainController::class, 'listDomain'])->name('domain.list');
        Route::get('/available', [DomainController::class, 'getListAvailableDomain'])->name('domain.list.available');
        Route::get('/refresh', [DomainController::class, 'refreshDomain'])->name('domain.refresh');
        Route::get('/list-url-path', [DomainController::class, 'listUrlPath'])->name('domain.list.url.path');
        Route::post('/store', [DomainController::class, 'store'])->name('domain.store');
        Route::get('/get-list-domain-for-tracking', [DomainController::class, 'getListDomainForTracking'])->name('domain.get.list.for.tracking');
    });

    Route::prefix('html-source')->group(function () {
        Route::get('/', [HtmlSourceController::class, 'listHtmlSource'])->name('html.source.list');
        Route::get('/{id}', [HtmlSourceController::class, 'showHtmlSource'])->name('html.source.show');
    });

    Route::prefix('users-tracking')->group(function () {
        Route::get('/', [UsersTrackingController::class, 'listTrackingEvent'])->name('users.tracking.list');
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

    Route::prefix('push-system')->group(function () {
        Route::get('/', [PushSystemController::class, 'listPushSystem'])->name('push.system.list');
        Route::get('/config/store', [PushSystemController::class, 'storePushSystemConfigByAdmin'])->name('push.system.config.store');
        Route::get('/user-active/list', [PushSystemController::class, 'listPushSystemUserActive'])->name('push.system.user.active.list');
        Route::post('/config/update/{id}', [PushSystemController::class, 'updatePushSystemConfig'])->name('push.system.config.update');
        Route::get('/count-current-push', [PushSystemController::class, 'getCountCurrentPush'])->name('push.system.count.current.push');
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
});
