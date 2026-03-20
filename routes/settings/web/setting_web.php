<?php

use App\Http\Controllers\V1\Settings\Web\PermissionController;
use App\Http\Controllers\V1\Settings\Web\RoleController;
use App\Http\Controllers\V1\Settings\Web\SubMenuController;
use Illuminate\Support\Facades\Route;


Route::prefix('setting')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::prefix('submenu')->group(function () {
            Route::get('list', [SubMenuController::class, 'index']);
            Route::get('global_search', [SubMenuController::class, 'global_search']);
            Route::get('generate-code', [SubMenuController::class, 'generateCode']);
            Route::get('{uuid}', [SubMenuController::class, 'show']);
            Route::post('add', [SubMenuController::class, 'store']);
            Route::put('{uuid}', [SubMenuController::class, 'update']);
            Route::delete('{uuid}', [SubMenuController::class, 'destroy']);
        });

        // Route::prefix('roles')->group(function () {
        //     Route::get('list', [RoleController::class, 'index']);
        //     Route::post('/assign-permissions/{id}', [RoleController::class, 'assignPermissionsWithMenu']);
        //     Route::put('/permissions/{id}', [RoleController::class, 'updateRolePermissions']);          // GET all roles
        //     Route::get('{id}', [RoleController::class, 'show']);         // GET single role
        //     Route::post('add', [RoleController::class, 'store']);          // POST create role
        //     Route::put('{id}', [RoleController::class, 'update']);       // PUT update role
        //     Route::delete('{id}', [RoleController::class, 'destroy']);   // DELETE role
        // });

        // Route::prefix('permissions')->group(function () {
        //     Route::get('list', [PermissionController::class, 'index']);         // GET all permissions
        //     Route::get('{id}', [PermissionController::class, 'show']);  // GET single permission
        //     Route::post('add', [PermissionController::class, 'store']);         // POST create permission
        //     Route::put('{id}', [PermissionController::class, 'update']); // PUT update permission
        //     Route::delete('{id}', [PermissionController::class, 'destroy']); // DELETE permission
        // });
    });
});
