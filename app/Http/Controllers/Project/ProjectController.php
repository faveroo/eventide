<?php

namespace App\Http\Controllers\Project;

use App\Actions\Project\CreateProjectAction;
use App\Data\Monitoring\EventData;
use App\Data\Monitoring\HealthCheckData;
use App\Data\Monitoring\IncidentData;
use App\Data\Monitoring\RuleResponseData;
use App\Data\Organization\WorkspaceOrganizationData;
use App\Data\Project\ProjectInputData;
use App\Data\Project\WorkspaceProjectData;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectRequest;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function show(Request $request, Organization $organization, Project $project): Response
    {
        Gate::authorize('view', $project);

        return Inertia::render('project/Show', [
            'project' => WorkspaceProjectData::fromModel($project)->toArray(),
            'organization' => WorkspaceOrganizationData::forUser($organization, $request->user())->toArray(),
            'events' => EventData::collect($project->events()->latest('occurred_at')->latest('id')->limit(100)->get()),
            'checks' => HealthCheckData::collect($project->checks()->latest('checked_at')->latest('id')->limit(100)->get()),
            'incidents' => IncidentData::collect($project->incidents()->latest('id')->limit(100)->get()),
            'rules' => RuleResponseData::collect($project->rules()->orderBy('name')->orderBy('id')->get()),
        ]);
    }

    public function store(ProjectRequest $request, Organization $organization, CreateProjectAction $action): RedirectResponse
    {
        $project = $action->handle($organization, ProjectInputData::from($request->validated()));

        return redirect()->route('project.show', [$organization->slug, $project->slug])->with('message', 'Projeto criado.');
    }

    public function update(ProjectRequest $request, Organization $organization, Project $project): RedirectResponse
    {
        $project->update(ProjectInputData::from($request->validated())->toArray());

        return back()->with('message', 'Projeto atualizado.');
    }

    public function destroy(Organization $organization, Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);
        $project->delete();

        return redirect('/?organization='.$organization->slug.'&tab=projects')->with('message', 'Projeto removido.');
    }

    public function rotateToken(Organization $organization, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);
        $token = Str::random(64);
        $project->forceFill(['api_token' => hash('sha256', $token)])->save();

        return back()->with('api_token', $token)->with('message', 'Token criado. Copie agora; ele será exibido apenas uma vez.');
    }

    public function rotateGithubSecret(Organization $organization, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);
        $secret = Str::random(64);
        $project->forceFill(['github_secret' => $secret])->save();

        return back()->with('github_secret', $secret)->with('message', 'Segredo criado. Copie agora; ele será exibido apenas uma vez.');
    }
}
