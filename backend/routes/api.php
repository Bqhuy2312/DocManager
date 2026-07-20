<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\FolderController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/guest-login', [AuthController::class, 'guestLogin']);
Route::get('/departments/options', [DepartmentController::class, 'options']);
Route::middleware(['auth:sanctum', 'guest.active'])->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/me/avatar', [AuthController::class, 'uploadAvatar']);
    Route::get('/settings', [SettingController::class, 'show']);
    Route::patch('/me/profile', [SettingController::class, 'updateProfile']);
    Route::patch('/me/settings', [SettingController::class, 'updateSettings']);
    Route::patch('/me/password', [SettingController::class, 'updatePassword']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/read', [NotificationController::class, 'deleteRead']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/documents/metadata', [DocumentController::class, 'metadata']);
    Route::get('/favorites', [DocumentController::class, 'favorites']);
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::get('/documents/{document}', [DocumentController::class, 'show']);
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview']);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download']);
    Route::post('/documents/{document}/favorite', [DocumentController::class, 'toggleFavorite']);
    Route::get('/folders', [FolderController::class, 'index']);
    Route::get('/folders/categories', [FolderController::class, 'categories']);

    Route::middleware('role:admin,editor')->group(function () {
        Route::post('/documents', [DocumentController::class, 'store']);
        Route::post('/documents/{document}/update-file', [DocumentController::class, 'updateFile']);
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);
        Route::post('/folders', [FolderController::class, 'store']);
        Route::patch('/folders/{folder}', [FolderController::class, 'update']);
        Route::delete('/folders/{folder}', [FolderController::class, 'destroy']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard/activities', [DashboardController::class, 'activities']);
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::post('/departments', [DepartmentController::class, 'store']);
        Route::patch('/departments/{department}', [DepartmentController::class, 'update']);
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);
        Route::get('/backups', [BackupController::class, 'index']);
        Route::post('/backups', [BackupController::class, 'store']);
        Route::post('/backups/restore', [BackupController::class, 'restore']);
        Route::post('/backups/{backup}/restore', [BackupController::class, 'restoreStored']);
        Route::get('/backups/{backup}/download', [BackupController::class, 'download']);
        Route::delete('/backups/{backup}', [BackupController::class, 'destroy']);
        Route::patch('/documents/{document}/approval', [DocumentController::class, 'approve']);
    });

});
