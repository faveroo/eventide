<?php

namespace App\Jobs;

use App\Models\AppEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReplayPendingEvents implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue(config('eventide.queue'));
    }

    public function handle(): void
    {
        AppEvent::whereNull('processed_at')
            ->whereHas('project', fn ($query) => $query->where('active', true)
                ->whereHas('organization', fn ($organization) => $organization->where('active', true)))
            ->chunkById(200, function ($events) {
                foreach ($events as $event) {
                    ProcessAppEvent::dispatch($event->id);
                }
            });
    }
}
