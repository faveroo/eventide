<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Project\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest'])->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register.form');
    Route::post('/register', [RegisterController::class, 'store'])->name('register');

    Route::get('/login', [LoginController::class, 'create'])->name('login.form');
    Route::post('/login', [LoginController::class, 'auth'])->name('login');
});

Route::get('/csrf-token', function () {
    return response()->json([
        'token' => csrf_token(),
    ]);
});

Route::middleware(['web', 'testing'])->group(function () {
    Route::post('/logout', LogoutController::class);

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations');

    Route::prefix('organization')->group(function () {
        Route::post('/', [OrganizationController::class, 'store'])->name('organization.store');
        Route::get('/create', [OrganizationController::class, 'create'])->name('organization.create');
        Route::get('/{organization:slug}', [OrganizationController::class, 'show'])->name('organization.show');
        Route::delete('/{organization:slug}', [OrganizationController::class, 'destroy'])->name('organization.destroy');
        Route::post('/{organization:slug}/restore', [OrganizationController::class, 'restore'])->withTrashed()->name('organization.restore');
        
        Route::get('{organization:slug}/projects', [ProjectController::class, 'index'])->name('projects');

        Route::prefix('{organization:slug}/project')->group(function () {
            Route::get('/{project:slug}', [ProjectController::class, 'show']);
        });
    });
});

require_once 'webhook.php';
