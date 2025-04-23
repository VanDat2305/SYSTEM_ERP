<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\AuthController;
use Modules\Users\Http\Controllers\PermissionController;
use Modules\Users\Http\Controllers\RoleController;
use Modules\Users\Http\Controllers\UserController;

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

// api_puplic
Route::prefix('v1')->group(function(){
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']); 
});


//api authe
Route::middleware(['auth:sanctum', \App\Http\Middleware\CustomSanctumAuth::class])->prefix('v1')->group(function () {
    Route::apiResource('users', UserController::class)->names('users');

    // routes/api.php
    Route::post('/logout', [AuthController::class, 'logout']);

    

    // Roles
    Route::resource('roles', RoleController::class)->only(['index', 'show', 'store', 'update']);
    Route::post('roles/{id}/permissions', [RoleController::class, 'assignPermissions'])->name('roles.assignPermissions');
    Route::post('roles/delete', [RoleController::class, 'destroy'])->name('roles.destroy');


    // Permissions
    Route::resource('permissions', PermissionController::class)->only(['index', 'show', 'store', 'update']);
    Route::post('permissions/delete', [PermissionController::class, 'destroy'])->name('permissions.destroy');
});