<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\HealthMonitor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Carbon;

class CheckProjectHealth implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 45;

    public function __construct(public int $projectId)
    {
        $this->onQueue(config('eventide.queue'));
    }

    /** @return list<WithoutOverlapping> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('health:'.$this->projectId))->dontRelease()->expireAfter(60)];
    }

    public function handle(HealthMonitor $monitor): void
    {
        $project = Project::find($this->projectId);
        if ($project && (! $project->last_checked_at || Carbon::parse($project->last_checked_at)
            ->lte(now()->subSeconds($project->check_interval_seconds ?? config('eventide.health_interval_seconds'))))) {
            $monitor->check($project);
        }
    }
}
