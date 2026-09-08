<?php

namespace App\Actions\Incident;

use App\Data\Monitoring\IncidentInputData;
use App\Models\Incident;
use App\Models\Project;
use App\Services\IncidentDetector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateIncidentAction
{
    public function __construct(private IncidentDetector $detector) {}

    public function handle(Project $project, IncidentInputData $data): Incident
    {
        return DB::transaction(function () use ($project, $data): Incident {
            Project::query()->lockForUpdate()->findOrFail($project->id);
            $incident = $project->incidents()->create([
                ...$data->toArray(), 'fingerprint' => hash('sha256', (string) Str::uuid()),
                'status' => 'investigating', 'opened_at' => now(), 'last_seen_at' => now(),
            ]);
            $this->detector->refreshProjectStatus($project);

            return $incident;
        }, 3);
    }
}
