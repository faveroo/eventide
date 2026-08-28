<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(Request $request, Organization $organization)
    {
        Gate::authorize('view', $organization);

        $projects = $organization->projects()->with('managerMembership:id,user_id', 'managerMembership.user:id,name,email,created_at')->get();

        return response()->json($projects);
    }
}
