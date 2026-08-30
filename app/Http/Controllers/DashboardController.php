<?php

namespace App\Http\Controllers;

use App\Data\Organization\ResponseOrganizationData;
use App\Data\Project\ResponseProjectData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizations = $user->organizationMemberships()->with(['organization.owner', 'role.permissions'])->paginate(3);
        $projects = $user->projects()->with('managerMembership.user', 'managerMembership.role.permissions')->paginate(3);

        $projects->through(fn ($project) => ResponseProjectData::fromModel($project));
        $organizations->through(fn ($organization) => ResponseOrganizationData::fromMembership($organization));

        return response()->json([
            'projects' => $projects,
            'organizations' => $organizations,
        ]);
    }
}
