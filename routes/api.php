<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\BuilderController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PropertyTypeController;
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
    Route::post('users/{user}', [UserController::class, 'update'])->middleware('permission:user-edit');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:user-delete');

    // Property Management
    Route::get('properties', [PropertyController::class, 'index'])->middleware('permission:property-list');
    Route::post('properties', [PropertyController::class, 'store'])->middleware('permission:property-create');
    Route::get('properties/{property}', [PropertyController::class, 'show'])->middleware('permission:property-list');
    Route::post('properties/{property}', [PropertyController::class, 'update'])->middleware('permission:property-edit');
    Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->middleware('permission:property-delete');

    // Category Management
    Route::get('categories', [CategoryController::class, 'index'])->middleware('permission:category-list');
    Route::post('categories', [CategoryController::class, 'store'])->middleware('permission:category-create');
    Route::get('categories/{category}', [CategoryController::class, 'show'])->middleware('permission:category-list');
    Route::put('categories/{category}', [CategoryController::class, 'update'])->middleware('permission:category-edit');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:category-delete');

    // Property Type Management
    Route::get('property-types', [PropertyTypeController::class, 'index'])->middleware('permission:property-type-list');
    Route::post('property-types', [PropertyTypeController::class, 'store'])->middleware('permission:property-type-create');
    Route::get('property-types/{id}', [PropertyTypeController::class, 'show'])->middleware('permission:property-type-list');
    Route::put('property-types/{id}', [PropertyTypeController::class, 'update'])->middleware('permission:property-type-edit');
    Route::delete('property-types/{id}', [PropertyTypeController::class, 'destroy'])->middleware('permission:property-type-delete');
});
