<?php

namespace App\Data\Project;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ProjectInputData extends Data
{
    public function __construct(
        public string $name,
        public string|null|Optional $description,
        public string|null|Optional $base_url,
        public string|null|Optional $check_status_url,
        public bool|Optional $active,
        public int|Optional $check_interval_seconds,
        public int|Optional $timeout_seconds,
        public int|Optional $failure_threshold,
        public int|Optional $latency_threshold_ms,
    ) {}
}
