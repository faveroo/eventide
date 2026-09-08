<?php

namespace App\Data\Monitoring;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

class HealthCheckData extends Data
{
    public function __construct(
        public int $id,
        public string $status,
        public ?int $response_status,
        public ?int $latency_ms,
        public ?string $error,
        public CarbonInterface $checked_at,
        public CarbonInterface $created_at,
    ) {}
}
