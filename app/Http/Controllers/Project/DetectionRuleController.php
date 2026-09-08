<?php

namespace App\Http\Controllers\Project;

use App\Data\Monitoring\DetectionRuleData;
use App\Http\Controllers\Controller;
use App\Http\Requests\DetectionRuleRequest;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DetectionRuleController extends Controller
{
    public function store(DetectionRuleRequest $request, Organization $organization, Project $project): RedirectResponse
    {
        $project->rules()->create(DetectionRuleData::from($request->validated())->toArray());

        return back()->with('message', 'Regra criada.');
    }

    public function update(DetectionRuleRequest $request, Organization $organization, Project $project, int $ruleId): RedirectResponse
    {
        $project->rules()->findOrFail($ruleId)->update(DetectionRuleData::from($request->validated())->toArray());

        return back()->with('message', 'Regra atualizada.');
    }

    public function destroy(Organization $organization, Project $project, int $ruleId): RedirectResponse
    {
        Gate::authorize('update', $project);
        $project->rules()->findOrFail($ruleId)->delete();

        return back()->with('message', 'Regra removida.');
    }
}
