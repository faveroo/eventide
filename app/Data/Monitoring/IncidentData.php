<?php

namespace App\Data\Monitoring;

use App\Data\Project\ProjectSummaryData;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

class IncidentData extends Data
{
    public function __construct(
        public int $id,
        public int $project_id,
        public string $title,
        public string $severity,
        public string $status,
        public int $event_count,
        public CarbonInterface $opened_at,
        public ?CarbonInterface $resolved_at,
        public CarbonInterface $created_at,
        public ?ProjectSummaryData $project = null,
    ) {}
}
