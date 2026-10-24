<?php

use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\InstallationController;
use App\Http\Controllers\Admin\RoleController;
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
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    // Super Admin Master Data & Role Management Routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('areas', AreaController::class)->except(['show']);
        Route::resource('installations', InstallationController::class)->except(['index', 'show']);
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    });
});

