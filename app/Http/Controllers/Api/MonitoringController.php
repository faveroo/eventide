<?php

namespace App\Http\Controllers\Api;

use App\Data\Monitoring\HealthCheckData;
use App\Http\Controllers\Controller;
use App\Models\HealthCheck;
use App\Models\Project;
use App\Services\IncidentDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    protected function project(Request $request): Project
    {
        $token = $request->bearerToken();
        abort_unless(is_string($token) && strlen($token) >= 32 && strlen($token) <= 512, 401, 'Invalid project token.');
        $project = Project::where('api_token', hash('sha256', $token))->first();
        abort_unless($project !== null, 401, 'Invalid project token.');
        abort_unless($project->active && $project->organization?->active, 403, 'Project is inactive.');

        return $project;
    }

    public function status(Request $request, IncidentDetector $detector): JsonResponse
    {
        $project = $this->project($request);

        return response()->json(['data' => [
            'project_id' => $project->id,
            'status' => $detector->refreshProjectStatus($project),
            'last_checked_at' => $project->last_checked_at,
        ]]);
    }

    public function checks(Request $request): JsonResponse
    {
        $project = $this->project($request);

        return response()->json(HealthCheckData::collect(HealthCheck::where('project_id', $project->id)->latest('id')->paginate(50)));
    }
}
