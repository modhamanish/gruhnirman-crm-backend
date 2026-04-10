<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\BuilderController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PropertyTypeController;
use App\Http\Controllers\Api\LeadSourceController;
use App\Http\Controllers\Api\LeadStatusController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\SiteVisitController;
use App\Http\Controllers\Api\FollowUpController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\AttendanceController;
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

    // Lead Source Management
    Route::get('lead-sources', [LeadSourceController::class, 'index'])->middleware('permission:lead-source-list');
    Route::post('lead-sources', [LeadSourceController::class, 'store'])->middleware('permission:lead-source-create');
    Route::get('lead-sources/{leadSource}', [LeadSourceController::class, 'show'])->middleware('permission:lead-source-list');
    Route::put('lead-sources/{leadSource}', [LeadSourceController::class, 'update'])->middleware('permission:lead-source-edit');
    Route::delete('lead-sources/{leadSource}', [LeadSourceController::class, 'destroy'])->middleware('permission:lead-source-delete');

    // Lead Status Management
    Route::get('lead-statuses', [LeadStatusController::class, 'index'])->middleware('permission:lead-status-list');
    Route::post('lead-statuses', [LeadStatusController::class, 'store'])->middleware('permission:lead-status-create');
    Route::get('lead-statuses/{leadStatus}', [LeadStatusController::class, 'show'])->middleware('permission:lead-status-list');
    Route::put('lead-statuses/{leadStatus}', [LeadStatusController::class, 'update'])->middleware('permission:lead-status-edit');
    Route::delete('lead-statuses/{leadStatus}', [LeadStatusController::class, 'destroy'])->middleware('permission:lead-status-delete');

    // Lead Management
    Route::get('leads', [LeadController::class, 'index'])->middleware('permission:lead-list');
    Route::post('leads', [LeadController::class, 'store'])->middleware('permission:lead-create');
    Route::get('leads/{lead}', [LeadController::class, 'show'])->middleware('permission:lead-list');
    Route::get('leads/{id}/suggested-properties', [LeadController::class, 'suggestedProperties'])->middleware('permission:lead-list');
    Route::put('leads/{lead}', [LeadController::class, 'update'])->middleware('permission:lead-edit');
    Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->middleware('permission:lead-delete');

    // Site Visit Management
    Route::get('site-visits', [SiteVisitController::class, 'index'])->middleware('permission:site-visit-list');
    Route::post('site-visits', [SiteVisitController::class, 'store'])->middleware('permission:site-visit-create');
    Route::get('site-visits/{id}', [SiteVisitController::class, 'show'])->middleware('permission:site-visit-list');
    Route::put('site-visits/{id}', [SiteVisitController::class, 'update'])->middleware('permission:site-visit-edit');
    Route::delete('site-visits/{id}', [SiteVisitController::class, 'destroy'])->middleware('permission:site-visit-delete');

    // Follow Up Management
    Route::get('follow-ups', [FollowUpController::class, 'index'])->middleware('permission:follow-up-list');
    Route::post('follow-ups', [FollowUpController::class, 'store'])->middleware('permission:follow-up-create');
    Route::get('follow-ups/{id}', [FollowUpController::class, 'show'])->middleware('permission:follow-up-list');
    Route::put('follow-ups/{id}', [FollowUpController::class, 'update'])->middleware('permission:follow-up-edit');
    Route::delete('follow-ups/{id}', [FollowUpController::class, 'destroy'])->middleware('permission:follow-up-delete');

    // Inquiry Management
    Route::get('inquiries', [InquiryController::class, 'index'])->middleware('permission:inquiry-list');
    Route::post('inquiries', [InquiryController::class, 'store'])->middleware('permission:inquiry-create');
    Route::get('inquiries/{id}', [InquiryController::class, 'show'])->middleware('permission:inquiry-list');
    Route::put('inquiries/{id}', [InquiryController::class, 'update'])->middleware('permission:inquiry-edit');
    Route::delete('inquiries/{id}', [InquiryController::class, 'destroy'])->middleware('permission:inquiry-delete');
    Route::post('inquiries/{id}/convert', [InquiryController::class, 'convertToLead'])->middleware('permission:inquiry-convert-to-lead');

    // Attendance Management
    Route::get('attendances', [AttendanceController::class, 'index'])->middleware('permission:attendance-list');
    Route::get('attendances/today-status', [AttendanceController::class, 'todayStatus']);
    Route::get('attendances/{id}', [AttendanceController::class, 'show'])->middleware('permission:attendance-list');
    Route::post('attendances/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('attendances/break-start', [AttendanceController::class, 'breakStart']);
    Route::post('attendances/break-end', [AttendanceController::class, 'breakEnd']);
    Route::post('attendances/check-out', [AttendanceController::class, 'checkOut']);
});
