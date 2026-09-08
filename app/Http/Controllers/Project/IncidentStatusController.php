<?php

namespace App\Http\Controllers\Project;

use App\Actions\Incident\UpdateIncidentStatusAction;
use App\Data\Monitoring\IncidentStatusData;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IncidentStatusController extends Controller
{
    public function __construct(private UpdateIncidentStatusAction $update) {}

    public function store(Request $request, Organization $organization, Project $project, int $incidentId): RedirectResponse
    {
        Gate::authorize('update', $project);
        $data = $request->validate(['status' => ['required', Rule::in(['investigating', 'identified', 'monitoring', 'resolved'])]]);
        $incident = $project->incidents()->findOrFail($incidentId);
        $this->update->handle($project, $incident, IncidentStatusData::from($data), $request->user());

        return back()->with('message', 'Status do incidente atualizado.');
    }
}
