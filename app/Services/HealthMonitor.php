<?php

namespace App\Services;

use App\Models\HealthCheck;
use App\Models\Incident;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthMonitor
{
    public function __construct(private SafeEndpoint $endpoint, private IncidentDetector $detector) {}

    public function check(Project $project): ?HealthCheck
    {
        $project = $project->fresh();
        if (! $project || ! $project->active || ! $project->organization?->active || ! $project->check_status_url) {
            return null;
        }
        $url = $project->check_status_url;
        $checkedAt = now();
        $start = hrtime(true);
        $responseStatus = null;
        $error = null;
        try {
            $response = $this->endpoint->get($url, $project->timeout_seconds);
            $responseStatus = $response->status();
            $healthy = $response->successful();
            $response->toPsrResponse()->getBody()->close();
            if (! $healthy) {
                $error = 'Endpoint returned HTTP '.$responseStatus.'.';
            }
        } catch (Throwable $exception) {
            $healthy = false;
            $error = $exception instanceof \InvalidArgumentException ? $exception->getMessage() : 'Endpoint request failed.';
        }
        $latency = (int) round((hrtime(true) - $start) / 1_000_000);
        $status = ! $healthy ? 'down' : ($latency >= ($project->latency_threshold_ms ?? config('eventide.health_slow_ms')) ? 'degraded' : 'operational');

        return DB::transaction(function () use ($project, $url, $checkedAt, $responseStatus, $error, $latency, $healthy, $status) {
            $project = Project::query()->lockForUpdate()->find($project->id);
            if (! $project || ! $project->active || ! $project->organization?->active || $project->check_status_url !== $url) {
                return null;
            }
            // Discard a stale result if another check finished more recently.
            if ($project->last_checked_at && Carbon::parse($project->last_checked_at)->gt($checkedAt)) {
                return null;
            }
            $check = HealthCheck::create([
                'project_id' => $project->id, 'status' => $status, 'response_status' => $responseStatus,
                'latency_ms' => $latency, 'error' => $error, 'checked_at' => $checkedAt,
            ]);
            $project->forceFill([
                'last_checked_at' => $checkedAt,
                'consecutive_failures' => $healthy ? 0 : $project->consecutive_failures + 1,
            ])->save();
            $fingerprint = hash('sha256', 'health');
            if ($project->consecutive_failures >= max(1, $project->failure_threshold ?? config('eventide.health_failure_threshold'))) {
                $incident = Incident::where('project_id', $project->id)->where('fingerprint', $fingerprint)
                    ->where('status', '!=', 'resolved')->first();
                $incident ??= Incident::create([
                    'project_id' => $project->id, 'fingerprint' => $fingerprint, 'status' => 'investigating',
                    'title' => $project->name.' is unavailable', 'severity' => 'critical',
                    'opened_at' => $checkedAt, 'last_seen_at' => $checkedAt,
                ]);
                $incident->increment('event_count');
                $incident->forceFill(['last_seen_at' => $checkedAt])->save();
            }
            $this->detector->refreshProjectStatus($project);

            return $check;
        }, 3);
    }
}
