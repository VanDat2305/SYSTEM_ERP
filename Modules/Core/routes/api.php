<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\ActivityLogController;
use Modules\Core\Http\Controllers\ObjectTypeController;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Core\Http\Controllers\MenuController;
use Modules\Core\Http\Controllers\ObjectItemController;
use Modules\Core\Http\Controllers\ObjectMetaController;
use Modules\Core\Http\Controllers\ProvinceController;

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

// Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
//     Route::apiResource('core', CoreController::class)->names('core');
// });
Route::prefix('v1')->group(function () {
        Route::get('/objects-cache', [ObjectTypeController::class, 'getAllCachedCategories']);
    Route::get('/objects-refesh-cache', [ObjectTypeController::class, 'refreshAllCategoryCache']);
});
Route::middleware([\App\Http\Middleware\CustomSanctumAuth::class])->prefix('v1')->group(function () {
    Route::get('menus', [MenuController::class, 'index']);  // Lấy danh sách menu
    Route::post('menus', [MenuController::class, 'store']); // Thêm menu mới
    Route::put('menus/{id}', [MenuController::class, 'update']); // Cập nhật menu
    Route::post('menus/delete', [MenuController::class, 'destroy']); // Xóa menu

    Route::apiResource('object-types', ObjectTypeController::class);
    Route::apiResource('objects', ObjectItemController::class);
    Route::apiResource('object-meta', ObjectMetaController::class);



    

    Route::get('/logs', [ActivityLogController::class, 'index']);

    Route::get('/vn-provinces', [ProvinceController::class, 'getProvinces']);
    Route::get('/vn-districts/{provinceCode}', [ProvinceController::class, 'getDistricts']);
    Route::get('/vn-wards/{districtCode}', [ProvinceController::class, 'getWards']);

});

use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

Route::prefix('v1')->group(function () {
    Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])
        ->name('sanctum.csrf-cookie');
});