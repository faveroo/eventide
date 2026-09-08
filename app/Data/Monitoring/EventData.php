<?php

namespace App\Data\Monitoring;

use App\Data\Project\ProjectSummaryData;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

class EventData extends Data
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public int $id,
        public string $type,
        public string $severity,
        public string $source,
        public ?string $message,
        public ?int $incident_id,
        public CarbonInterface $occurred_at,
        public CarbonInterface $created_at,
        public array $payload = [],
        public ?ProjectSummaryData $project = null,
    ) {}
}
