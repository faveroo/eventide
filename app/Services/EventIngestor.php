<?php

namespace App\Services;

use App\Data\Monitoring\EventInputData;
use App\Jobs\ProcessAppEvent;
use App\Models\AppEvent;
use App\Models\Project;
use Throwable;

class EventIngestor
{
    public function ingest(Project $project, EventInputData $data, string $source = 'application'): AppEvent
    {
        abort_unless($project->active && $project->organization?->active, 403, 'Project is inactive.');
        // The unique database key is the arbiter for concurrent delivery retries.
        $event = AppEvent::query()->firstOrCreate([
            'project_id' => $project->id,
            'source' => $source,
            'external_id' => $data->external_id,
        ], [
            'type' => $data->type,
            'severity' => $data->severity,
            'message' => $data->message,
            'payload' => $data->payload,
            'occurred_at' => $data->occurred_at ?? now(),
        ]);
        if (! $event->processed_at) {
            try {
                ProcessAppEvent::dispatch($event->id)->afterCommit();
            } catch (Throwable $exception) {
                // Acceptance is durable even when the broker is unavailable.
                // ReplayPendingEvents will retry dispatching this inbox row.
                report($exception);
            }
        }

        return $event;
    }
}
