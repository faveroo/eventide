<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Project\DetectionRuleController;
use App\Http\Controllers\Project\IncidentController;
use App\Http\Controllers\Project\IncidentNoteController;
use App\Http\Controllers\Project\IncidentStatusController;
use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register.form');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1,register')->name('register');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'auth'])->middleware('throttle:5,1,login')->name('login.store');
});
Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]));
Route::middleware('auth')->scopeBindings()->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
    foreach (['dashboard', 'projects', 'incidents', 'events', 'organizations', 'settings'] as $tab) {
        Route::get('/'.$tab, [WorkspaceController::class, 'index'])->defaults('tab', $tab)->name('workspace.'.$tab);
    }
    Route::post('/organization', [OrganizationController::class, 'store'])->name('organization.store');
    Route::get('/organization/create', [WorkspaceController::class, 'index'])->defaults('tab', 'organizations')->name('organization.create');
    Route::prefix('organization/{organization:slug}')->group(function () {
        Route::get('/', [WorkspaceController::class, 'index'])->name('organization.show');
        Route::match(['post', 'patch'], '/', [OrganizationController::class, 'update'])->name('organization.update');
        Route::delete('/', [OrganizationController::class, 'destroy'])->name('organization.destroy');
        Route::post('/restore', [OrganizationController::class, 'restore'])->withTrashed()->name('organization.restore');
        Route::post('/members', [OrganizationController::class, 'member'])->name('organization.members.store');
        Route::delete('/members/{userId}', [OrganizationController::class, 'removeMember'])->whereNumber('userId')->name('organization.members.destroy');
        Route::get('/projects', [WorkspaceController::class, 'index'])->defaults('tab', 'projects')->name('projects');
        Route::post('/project', [ProjectController::class, 'store'])->name('project.store');
        Route::prefix('project/{project:slug}')->group(function () {
            Route::get('/', [ProjectController::class, 'show'])->name('project.show');
            Route::match(['post', 'patch'], '/', [ProjectController::class, 'update'])->name('project.update');
            Route::delete('/', [ProjectController::class, 'destroy'])->name('project.destroy');
            Route::post('/rotate-token', [ProjectController::class, 'rotateToken'])->name('project.rotate-token');
            Route::post('/rotate-github-secret', [ProjectController::class, 'rotateGithubSecret'])->name('project.rotate-github-secret');
            Route::post('/rules', [DetectionRuleController::class, 'store'])->name('project.rules.store');
            Route::match(['post', 'patch'], '/rules/{ruleId}', [DetectionRuleController::class, 'update'])->whereNumber('ruleId')->name('project.rules.update');
            Route::delete('/rules/{ruleId}', [DetectionRuleController::class, 'destroy'])->whereNumber('ruleId')->name('project.rules.destroy');
            Route::post('/incidents', [IncidentController::class, 'store'])->name('project.incidents.store');
            Route::get('/incidents/{incidentId}', [IncidentController::class, 'show'])->whereNumber('incidentId')->name('project.incidents.show');
            Route::post('/incidents/{incidentId}/status', [IncidentStatusController::class, 'store'])->whereNumber('incidentId')->name('project.incidents.status');
            Route::post('/incidents/{incidentId}/notes', [IncidentNoteController::class, 'store'])->whereNumber('incidentId')->name('project.incidents.notes');
        });
    });
});
