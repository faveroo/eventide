<?php

namespace App\Data\Monitoring;

use Spatie\LaravelData\Data;

class EventInputData extends Data
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $external_id,
        public string $type,
        public string $severity = 'error',
        public ?string $message = null,
        public array $payload = [],
        public ?string $occurred_at = null,
    ) {}
}
