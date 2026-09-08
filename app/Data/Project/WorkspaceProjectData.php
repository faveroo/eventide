<?php

namespace App\Data\Project;

use App\Models\Project;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Data;

class WorkspaceProjectData extends Data
{
    /** @param array<string, bool> $permissions */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public bool $active,
        public ?string $base_url,
        public ?string $check_status_url,
        public string $status,
        public ?CarbonInterface $last_checked_at,
        public int $check_interval_seconds,
        public int $timeout_seconds,
        public int $failure_threshold,
        public int $latency_threshold_ms,
        public bool $has_api_token,
        public bool $has_github_secret,
        public array $permissions,
    ) {}

    public static function fromModel(Project $project): self
    {
        return new self(
            $project->id, $project->name, $project->slug, $project->description,
            $project->active, $project->base_url, $project->check_status_url,
            $project->status ?? 'unknown', $project->last_checked_at,
            $project->check_interval_seconds ?? 60, $project->timeout_seconds ?? 10,
            $project->failure_threshold ?? 3, $project->latency_threshold_ms ?? 2000,
            ! empty($project->getRawOriginal('api_token')), ! empty($project->getRawOriginal('github_secret')),
            ['update' => Gate::allows('update', $project), 'delete' => Gate::allows('delete', $project)],
        );
    }
}
