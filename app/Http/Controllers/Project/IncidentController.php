<?php

namespace App\Http\Controllers\Project;

use App\Actions\Incident\CreateIncidentAction;
use App\Data\Monitoring\EventData;
use App\Data\Monitoring\IncidentData;
use App\Data\Monitoring\IncidentInputData;
use App\Data\Monitoring\IncidentNoteData;
use App\Data\Organization\WorkspaceOrganizationData;
use App\Data\Project\WorkspaceProjectData;
use App\Http\Controllers\Controller;
use App\Models\IncidentNote;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    public function __construct(private CreateIncidentAction $create) {}

    public function store(Request $request, Organization $organization, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])]]);
        $incident = $this->create->handle($project, IncidentInputData::from($data));

        return redirect()->route('project.incidents.show', [$organization->slug, $project->slug, $incident->id])->with('message', 'Incidente criado.');
    }

    public function show(Request $request, Organization $organization, Project $project, int $incidentId): Response
    {
        Gate::authorize('view', $project);
        $incident = $project->incidents()->findOrFail($incidentId);

        return Inertia::render('incident/Show', [
            'incident' => IncidentData::from($incident)->toArray(),
            'project' => WorkspaceProjectData::fromModel($project)->toArray(),
            'organization' => WorkspaceOrganizationData::forUser($organization, $request->user())->toArray(),
            'events' => EventData::collect($incident->events()->latest('occurred_at')->latest('id')->limit(100)->get()),
            'notes' => IncidentNoteData::collect(IncidentNote::where('incident_id', $incident->id)->with('user:id,name,email')->oldest('id')->get()),
        ]);
    }
}
