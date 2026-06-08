<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\FolderController;
use App\Http\Controllers\Api\UserController;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/me/avatar', [AuthController::class, 'uploadAvatar']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/documents/metadata', [DocumentController::class, 'metadata']);
    Route::get('/favorites', [DocumentController::class, 'favorites']);
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::get('/documents/{document}', [DocumentController::class, 'show']);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download']);
    Route::post('/documents/{document}/favorite', [DocumentController::class, 'toggleFavorite']);
    Route::get('/folders', [FolderController::class, 'index']);
    Route::get('/folders/categories', [FolderController::class, 'categories']);

    Route::middleware('role:admin,editor')->group(function () {
        Route::post('/documents', [DocumentController::class, 'store']);
        Route::post('/folders', [FolderController::class, 'store']);
        Route::delete('/folders/{folder}', [FolderController::class, 'destroy']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::post('/departments', [DepartmentController::class, 'store']);
        Route::patch('/departments/{department}', [DepartmentController::class, 'update']);
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);
        Route::patch('/documents/{document}/approval', [DocumentController::class, 'approve']);
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);
    });

});
