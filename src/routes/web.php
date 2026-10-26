<?php

use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\InstallationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::match(['get', 'post'], '/logout', [AuthController::class, 'destroy'])->name('logout');


    // Role Assignments & User Roles Screen (Super Admin, SM, SAM)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('users-roles', [UserRoleController::class, 'index'])->name('users-roles.index');
        Route::post('users-roles', [UserRoleController::class, 'store'])->name('users-roles.store');
        Route::delete('users-roles/{roleAssignment}', [UserRoleController::class, 'destroy'])->name('users-roles.destroy');
    });

    // Super Admin Master Data & Role Architecture Routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('areas', AreaController::class)->except(['show']);
        Route::resource('installations', InstallationController::class)->except(['index', 'show']);
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    });
});


