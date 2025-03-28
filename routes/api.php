<?php

use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\WorkspaceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

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
    Route::post('/workspace/{workspace}/join', [WorkspaceController::class, 'joinPublicWorkspace']);
    Route::post('/workspace/{workspace}/add-member', [WorkspaceController::class, 'addMember']);
    Route::post('/workspace/remove-members', [WorkspaceController::class, 'removeMember']);
    Route::get('/workspace/{workspace}/members', [WorkspaceController::class, 'listMembers']);
    
});