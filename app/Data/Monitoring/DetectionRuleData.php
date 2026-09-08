<?php

namespace App\Data\Monitoring;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class DetectionRuleData extends Data
{
    public function __construct(
        public string $name,
        public string $event_type,
        public int $threshold,
        public int $window_seconds,
        public string $severity,
        public bool|Optional $enabled,
    ) {}
}
