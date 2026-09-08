<?php

namespace App\Data\Monitoring;

use Spatie\LaravelData\Data;

class WorkspaceStatsData extends Data
{
    public function __construct(
        public int $projects,
        public int $operational,
        public int $unhealthy,
        public int $open_incidents,
    ) {}
}
