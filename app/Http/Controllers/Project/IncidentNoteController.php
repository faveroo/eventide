<?php

namespace App\Http\Controllers\Project;

use App\Data\Monitoring\IncidentNoteInputData;
use App\Http\Controllers\Controller;
use App\Models\IncidentNote;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IncidentNoteController extends Controller
{
    public function store(Request $request, Organization $organization, Project $project, int $incidentId): RedirectResponse
    {
        Gate::authorize('update', $project);
        $incident = $project->incidents()->findOrFail($incidentId);
        $data = $request->validate(['body' => ['required', 'string', 'max:10000']]);
        IncidentNote::create([...IncidentNoteInputData::from($data)->toArray(), 'incident_id' => $incident->id, 'user_id' => $request->user()->id]);

        return back()->with('message', 'Nota adicionada.');
    }
}
