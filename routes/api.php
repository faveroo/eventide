<?php

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GithubWebhookController;
use App\Http\Controllers\Api\MonitoringController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:120,1')->group(function () {
    Route::post('events', [EventController::class, 'store'])->name('api.events.store');
    Route::get('status', [MonitoringController::class, 'status'])->name('api.status');
    Route::get('health-checks', [MonitoringController::class, 'checks'])->name('api.health-checks.index');
    Route::post('webhooks/github/{project}', GithubWebhookController::class)->whereNumber('project')->name('api.webhooks.github');
});
