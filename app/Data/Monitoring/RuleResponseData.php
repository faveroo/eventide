<?php

namespace App\Data\Monitoring;

use Spatie\LaravelData\Data;

class RuleResponseData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $event_type,
        public int $threshold,
        public int $window_seconds,
        public string $severity,
        public bool $enabled,
    ) {}
}
