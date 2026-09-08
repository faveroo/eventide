<?php

namespace App\Services;

use App\Models\AppEvent;
use App\Models\DetectionRule;
use App\Models\HealthCheck;
use App\Models\Incident;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class IncidentDetector
{
    public function process(int $eventId): void
    {
        DB::transaction(function () use ($eventId) {
            $event = AppEvent::find($eventId);
            if (! $event) {
                return;
            }
            // Every automatic incident writer serializes on the project row.
            $project = Project::query()->lockForUpdate()->find($event->project_id);
            $event = AppEvent::query()->lockForUpdate()->find($eventId);
            if (! $project || ! $event || $event->processed_at) {
                return;
            }
            if (! $project->active || ! $project->organization?->active) {
                return;
            }
            $configured = DetectionRule::where('project_id', $project->id)
                ->whereIn('event_type', [$event->type, '*'])->orderBy('id')->get();
            $rules = $configured->filter(fn ($rule) => $rule->enabled);
            if ($configured->isEmpty() && in_array($event->severity, ['error', 'critical'], true)) {
                $rules->push(new DetectionRule([
                    'name' => $event->type,
                    'event_type' => $event->type,
                    'threshold' => config('eventide.event_threshold'),
                    'window_seconds' => config('eventide.event_window_seconds'),
                    'severity' => $event->severity === 'critical' ? 'critical' : 'high',
                ]));
            }
            foreach ($rules as $rule) {
                $fingerprint = hash('sha256', 'event:'.($rule->id ?? 'default').':'.$event->type);
                $incident = Incident::where('project_id', $project->id)->where('fingerprint', $fingerprint)
                    ->where('status', '!=', 'resolved')->first();
                // Receipt time avoids trusting producer clocks. ID bounds make replay deterministic.
                $candidates = AppEvent::where('project_id', $project->id)->where('type', $event->type)
                    ->where('id', '<=', $event->id)
                    ->where('created_at', '>=', $event->created_at->copy()->subSeconds(max(1, $rule->window_seconds)))
                    ->whereNull('incident_id');
                if (! $rule->exists) {
                    $candidates->whereIn('severity', ['error', 'critical']);
                }
                if (! $incident && (clone $candidates)->count() < max(1, $rule->threshold)) {
                    continue;
                }
                $incident ??= Incident::create([
                    'project_id' => $project->id,
                    'detection_rule_id' => $rule->id,
                    'fingerprint' => $fingerprint,
                    'title' => $rule->name.' — '.$project->name,
                    'severity' => $rule->severity,
                    'opened_at' => $event->created_at,
                    'last_seen_at' => $event->created_at,
                ]);
                $count = $candidates->update(['incident_id' => $incident->id]);
                $incident->event_count += $count;
                $incident->last_seen_at = max($incident->last_seen_at, $event->created_at);
                $incident->save();
                // An event belongs to one incident; first matching threshold wins.
                break;
            }
            $event->processed_at = now();
            $event->save();
            $this->refreshProjectStatus($project);
        }, 3);
    }

    public function refreshProjectStatus(Project $project): string
    {
        return DB::transaction(function () use ($project) {
            $locked = Project::query()->lockForUpdate()->findOrFail($project->id);
            $latest = HealthCheck::where('project_id', $locked->id)->latest('id')->first();
            $status = 'unknown';
            if ($locked->active && $locked->organization?->active) {
                $staleSeconds = max(config('eventide.health_stale_seconds'), ($locked->check_interval_seconds ?? 60) * 3);
                if ($latest && $latest->checked_at->gte(now()->subSeconds($staleSeconds))) {
                    $status = $locked->consecutive_failures >= max(1, $locked->failure_threshold ?? config('eventide.health_failure_threshold'))
                        ? 'down' : ($latest->status === 'operational' ? 'operational' : 'degraded');
                }
                if ($status !== 'down' && Incident::where('project_id', $locked->id)->where('status', '!=', 'resolved')->exists()) {
                    $status = 'degraded';
                }
            }
            $locked->forceFill(['status' => $status])->save();
            $project->status = $status;

            return $status;
        }, 3);
    }
}
