<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\HealthMonitor;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class CheckProjectHealth implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 45;

    public int $uniqueFor = 3600;

    public function __construct(public int $projectId)
    {
        $this->onQueue(config('eventide.queue'));
    }

    public function uniqueId(): string
    {
        return (string) $this->projectId;
    }

    /** @return list<WithoutOverlapping> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('health:'.$this->projectId))->dontRelease()->expireAfter(60)];
    }

    public function handle(HealthMonitor $monitor): void
    {
        $project = Project::find($this->projectId);
        if ($project?->isHealthCheckDue()) {
            $monitor->check($project);
        }
    }
}
