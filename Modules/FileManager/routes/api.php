<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Modules\FileManager\Http\Controllers\FileController;
use Modules\FileManager\Http\Controllers\FolderController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/
Route::middleware(['auth:sanctum', \App\Http\Middleware\CustomSanctumAuth::class])->prefix('v1')->group(function () {
    // Route::apiResource('filemanager', FileManagerController::class)->names('filemanager');
    Route::prefix('folders')->group(function() {
        Route::get('/',  [FolderController::class, 'index']);
        Route::post('/', [FolderController::class, 'store']);
        Route::delete('/{id}', [FolderController::class, 'destroy']);
    });
    
    Route::prefix('files')->group(function() {
        Route::get('/', [FileController::class, 'index']);
        Route::post('/', [FileController::class, 'store']);
        Route::delete('/{id}', [FileController::class, 'destroy']);
    });
});

Route::prefix('v1')->get('/file/{id}', [FileController::class, 'serve']);
