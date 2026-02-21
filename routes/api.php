<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\BuilderController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PropertyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles.permissions');
    });

    // Roles & Permissions
    Route::apiResource('roles', RoleController::class);
    Route::get('permissions', [RoleController::class, 'getPermissions']);

    // Builders CRUD with Permissions
    Route::get('builders', [BuilderController::class, 'index'])->middleware('permission:builder-list');
    Route::post('builders', [BuilderController::class, 'store'])->middleware('permission:builder-create');
    Route::get('builders/{builder}', [BuilderController::class, 'show'])->middleware('permission:builder-list');
    Route::post('builders/{builder}', [BuilderController::class, 'update'])->middleware('permission:builder-edit');
    Route::delete('builders/{builder}', [BuilderController::class, 'destroy'])->middleware('permission:builder-delete');

    // User Management
    Route::get('users', [UserController::class, 'index'])->middleware('permission:user-list');
    Route::post('users', [UserController::class, 'store'])->middleware('permission:user-create');
    Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:user-list');
    Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:user-edit');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:user-delete');

    // Property Management
    Route::get('properties', [PropertyController::class, 'index'])->middleware('permission:property-list');
    Route::post('properties', [PropertyController::class, 'store'])->middleware('permission:property-create');
    Route::get('properties/{property}', [PropertyController::class, 'show'])->middleware('permission:property-list');
    Route::post('properties/{property}', [PropertyController::class, 'update'])->middleware('permission:property-edit');
    Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->middleware('permission:property-delete');
});
