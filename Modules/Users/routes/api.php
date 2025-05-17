<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\AuthController;
use Modules\Users\Http\Controllers\OtpChallengeController;
use Modules\Users\Http\Controllers\PermissionController;
use Modules\Users\Http\Controllers\RoleController;
use Modules\Users\Http\Controllers\UserController;
use Modules\Users\Http\Controllers\PasswordResetController;
use Modules\Users\Http\Controllers\TwoFactorAuthController;
use Modules\Users\Http\Controllers\VerificationController;

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
    // quen mat khau
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'verifyToken'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
    // 2fa
    Route::post('/two-factor-challenge', [OtpChallengeController::class, 'store']);
    //refresh token
    Route::post('/refresh', [AuthController::class, 'refreshToken'])->middleware('auth:sanctum');
    //verify email
    Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
     ->name('email.verify')
    ->middleware('signed');
    Route::post('email/resend', [VerificationController::class, 'resend']);
});


//api authe
Route::middleware(['auth:sanctum', \App\Http\Middleware\CustomSanctumAuth::class])->prefix('v1')->group(function () {
    Route::apiResource('users', UserController::class)->names('users');
    Route::post('users/delete', [UserController::class, 'destroy'])->name('users.delete');
    // routes/api.php
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/two-factor-authentication', [TwoFactorAuthController::class, 'store']);
    Route::post('/confirmed-two-factor-authentication', [TwoFactorAuthController::class, 'confirm']);
    Route::delete('/two-factor-authentication', [TwoFactorAuthController::class, 'destroy']);
    Route::get('/two-factor-qr-code', [TwoFactorAuthController::class, 'showQrCode']);
    Route::get('/two-factor-recovery-codes', [TwoFactorAuthController::class, 'showRecoveryCodes']);

    
    // Roles
    Route::resource('roles', RoleController::class)->only(['index', 'show', 'store', 'update']);
    Route::post('roles/{id}/permissions', [RoleController::class, 'assignPermissions'])->name('roles.assignPermissions');
    Route::post('roles/delete', [RoleController::class, 'destroy'])->name('roles.destroy');


    // Permissions
    Route::resource('permissions', PermissionController::class)->only(['index', 'show', 'store', 'update']);
    Route::post('permissions/delete', [PermissionController::class, 'destroy'])->name('permissions.destroy');
});