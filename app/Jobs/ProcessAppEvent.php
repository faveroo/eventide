<?php

namespace App\Jobs;

use App\Services\IncidentDetector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAppEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $eventId)
    {
        $this->onQueue(config('eventide.queue'));
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(IncidentDetector $detector): void
    {
        $detector->process($this->eventId);
    }
}
