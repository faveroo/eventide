<?php

namespace App\Http\Controllers\Api;

use App\Data\Monitoring\EventAcceptedData;
use App\Data\Monitoring\EventInputData;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\EventIngestor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GithubWebhookController extends Controller
{
    public function __invoke(Request $request, Project $project, EventIngestor $ingestor): JsonResponse
    {
        abort_unless($project->active && $project->organization?->active, 403, 'Project is inactive.');
        $body = $request->getContent();
        abort_if(strlen($body) > 1048576, 413, 'Webhook payload exceeds 1 MiB.');
        $secret = $project->github_secret;
        $signature = $request->header('X-Hub-Signature-256', '');
        abort_unless(is_string($secret) && $secret !== '' && hash_equals('sha256='.hash_hmac('sha256', $body, $secret), $signature), 401, 'Invalid webhook signature.');
        $headers = validator([
            'delivery' => $request->header('X-GitHub-Delivery'),
            'event' => $request->header('X-GitHub-Event'),
        ], [
            'delivery' => ['required', 'string', 'max:128'],
            'event' => ['required', 'string', 'max:100', 'regex:/^[a-z_]+$/'],
        ])->validate();
        $payload = json_decode($body, true);
        abort_unless(is_array($payload) && json_last_error() === JSON_ERROR_NONE, 422, 'Invalid JSON payload.');
        $failed = in_array(data_get($payload, 'workflow_run.conclusion'), ['failure', 'timed_out', 'action_required'], true)
            || in_array(data_get($payload, 'deployment_status.state'), ['failure', 'error'], true);
        $event = $ingestor->ingest($project, EventInputData::from([
            'external_id' => $headers['delivery'],
            'type' => 'github.'.$headers['event'],
            'severity' => $failed ? 'error' : 'info',
            'message' => 'GitHub '.$headers['event'],
            'payload' => $payload,
        ]), 'github');

        return response()->json(['data' => new EventAcceptedData($event->id)], 202);
    }
}
