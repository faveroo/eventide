<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\IncidentDetector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ScheduleHealthChecks implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue(config('eventide.queue'));
    }

    public function handle(IncidentDetector $detector): void
    {
        Project::query()->chunkById(200, function ($projects) use ($detector) {
            foreach ($projects as $project) {
                $detector->refreshProjectStatus($project);
                if ($project->active && $project->organization?->active && $project->check_status_url) {
                    CheckProjectHealth::dispatch($project->id);
                }
            }
        });
    }
}
