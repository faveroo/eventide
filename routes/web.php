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
        // lista as orgs
        Route::post('/', [OrganizationController::class, 'store'])->name('organization.store');
        // formulario para criar org
        Route::get('/create', [OrganizationController::class, 'create'])->name('organization.create');
        // pág da org
        Route::get('/{organization:slug}', [OrganizationController::class, 'show'])->name('organization.show');
        // deleta a org
        Route::delete('/{organization:slug}', [OrganizationController::class, 'destroy'])->name('organization.destroy');
        Route::post('/{organization:slug}/restore', [OrganizationController::class, 'restore'])->withTrashed()->name('organization.restore');

        // lista os projetos da org
        Route::get('{organization:slug}/projects', [ProjectController::class, 'index']);

        Route::prefix('{organization:slug}/project')->group(function () {
            // pág de um projeto
            Route::get('/{project:slug}', [ProjectController::class, 'show']);
        });
    });
});

require_once 'webhook.php';
