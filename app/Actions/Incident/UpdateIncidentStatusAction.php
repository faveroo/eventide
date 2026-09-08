<?php

namespace App\Actions\Incident;

use App\Data\Monitoring\IncidentStatusData;
use App\Models\Incident;
use App\Models\IncidentNote;
use App\Models\Project;
use App\Models\User;
use App\Services\IncidentDetector;
use Illuminate\Support\Facades\DB;

class UpdateIncidentStatusAction
{
    public function __construct(private IncidentDetector $detector) {}

    public function handle(Project $project, Incident $incident, IncidentStatusData $data, User $user): void
    {
        DB::transaction(function () use ($project, $incident, $data, $user): void {
            Project::query()->lockForUpdate()->findOrFail($project->id);
            $locked = $project->incidents()->lockForUpdate()->findOrFail($incident->id);
            if ($locked->status === $data->status) {
                return;
            }
            $previous = $locked->status;
            $locked->update(['status' => $data->status, 'resolved_at' => $data->status === 'resolved' ? now() : null]);
            IncidentNote::create([
                'incident_id' => $locked->id, 'user_id' => $user->id,
                'body' => 'Estado alterado: '.$previous.' → '.$data->status.'.',
            ]);
            $this->detector->refreshProjectStatus($project);
        }, 3);
    }
}
