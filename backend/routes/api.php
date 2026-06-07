<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\FolderController;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/me/avatar', [AuthController::class, 'uploadAvatar']);

    Route::post('/logout', [AuthController::class, 'logout']);

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
        Route::patch('/documents/{document}/approval', [DocumentController::class, 'approve']);
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);
    });

});
