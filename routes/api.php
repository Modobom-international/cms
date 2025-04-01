<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BoardController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\WorkspaceController;
use App\Http\Controllers\API\ListBoardController;
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
    
    //list
    Route::post('/create-list', [ListBoardController::class, 'store']); // Tạo Board
    Route::get('/list/{id}', [ListBoardController::class, 'show']);
    Route::get('/board/{boardId}/lists', [ListBoardController::class, 'index']); // Lấy danh sách Board
    Route::post('/update-list/{id}', [ListBoardController::class, 'update']); // Cập nhật Board
    Route::delete('/delete-board/{id}', [ListBoardController::class, 'destroy']); // Xóa Board
    
});