<?php

use App\Jobs\ReplayPendingEvents;
use App\Jobs\ScheduleHealthChecks;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ReplayPendingEvents)->everyMinute()->withoutOverlapping();
Schedule::job(new ScheduleHealthChecks)->everyThirtySeconds()->withoutOverlapping();
