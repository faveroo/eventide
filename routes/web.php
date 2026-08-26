<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Organization\OrganizationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest'])->group(function() {
    Route::get('/register', [RegisterController::class, 'create'])->name('register.form');
    Route::post('/register', [RegisterController::class, 'store'])->name('register');

    Route::get('/login', [LoginController::class, 'create'])->name('login.form');
    Route::post('/login', [LoginController::class, 'auth'])->name('login');
});

Route::get('/csrf-token', function() {
    return response()->json([
        'token' => csrf_token()
    ]);
});

Route::middleware(['web'])->group(function() {
    Route::post('/logout', LogoutController::class);
    
    Route::get('/', function() {
        return response()->json();
    })->name('dashboard');

    Route::prefix('organization')->group(function() {
        Route::get('/', [OrganizationController::class, 'index'])->name('organization');
        Route::get('/create', [OrganizationController::class, 'create'])->name('organization.create');
        Route::post('/', [OrganizationController::class, 'store'])->name('organization.store');

        Route::prefix('{organization}/projects')->group(function() {
            Route::get('/', function(Request $request) {
                return response()->json($request);
            });
        });
    });
});


require_once 'webhook.php';