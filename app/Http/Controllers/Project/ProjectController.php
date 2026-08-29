<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(Request $request, Organization $organization): JsonResponse
    {
        Gate::authorize('view', $organization);

        $slug = trim((string) $request->input('filter.slug', $request->input('slug', '')));

        $projects = $organization
            ->projects()
            ->with('managerMembership:id,user_id', 'managerMembership.user:id,name,email,created_at')
            ->when(
                $slug !== '',
                fn ($query) => $query->whereLike(
                    'slug',
                    "%{$slug}%"
                )
            )
            ->paginate(15);

        return response()->json($projects);
    }
}
