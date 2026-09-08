<?php

namespace App\Http\Controllers;

use App\Data\Monitoring\EventData;
use App\Data\Monitoring\IncidentData;
use App\Data\Monitoring\WorkspaceStatsData;
use App\Data\Organization\WorkspaceOrganizationData;
use App\Data\Project\WorkspaceProjectData;
use App\Models\AppEvent;
use App\Models\Incident;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function index(Request $request, ?Organization $organization = null): Response
    {
        $organizations = Organization::query()->where(function ($query) use ($request) {
            $query->where('owner_id', $request->user()->id)->orWhereHas('memberships', fn ($q) => $q->where('user_id', $request->user()->id));
        })->orderBy('name')->get(['id', 'name', 'slug']);
        if (! $organization && $request->filled('organization')) {
            $organization = Organization::where('slug', $request->string('organization')->toString())->firstOrFail();
        }
        $organization ??= $organizations->first() ? Organization::find($organizations->first()->id) : null;
        if ($organization) {
            Gate::authorize('view', $organization);
        }
        $projects = $organization ? $organization->projects()->with('organization')->latest('id')->get() : collect();
        $tab = $request->query('tab', $request->route('tab', 'dashboard'));
        if (! in_array($tab, ['dashboard', 'projects', 'incidents', 'events', 'organizations', 'settings'], true)) {
            $tab = 'dashboard';
        }

        $incidentQuery = Incident::whereIn('project_id', $projects->pluck('id'));
        $openIncidents = (clone $incidentQuery)->where('status', '!=', 'resolved')->count();

        return Inertia::render('workspace/Index', [
            'organizations' => $organizations,
            'organization' => $organization ? WorkspaceOrganizationData::forUser($organization, $request->user())->toArray() : null,
            'projects' => $projects->map(fn (Project $project) => WorkspaceProjectData::fromModel($project)->toArray()),
            'stats' => new WorkspaceStatsData($projects->count(), $projects->where('status', 'operational')->count(), $projects->whereIn('status', ['degraded', 'down'])->count(), $openIncidents),
            'incidents' => IncidentData::collect($incidentQuery->when($tab === 'dashboard', fn ($query) => $query->where('status', '!=', 'resolved'))->with('project:id,name,slug,organization_id')->latest('id')->limit(50)->get()),
            'events' => EventData::collect(AppEvent::whereIn('project_id', $projects->pluck('id'))->with('project:id,name,slug,organization_id')->latest('occurred_at')->latest('id')->limit(50)->get()),
            'activeTab' => $tab,
            'archivedOrganizations' => Organization::onlyTrashed()->where('owner_id', $request->user()->id)->get(['id', 'name', 'slug', 'deleted_at']),
        ]);
    }
}
