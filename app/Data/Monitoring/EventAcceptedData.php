<?php

namespace App\Data\Monitoring;

use Spatie\LaravelData\Data;

class EventAcceptedData extends Data
{
    public function __construct(
        public int $id,
        public bool $accepted = true,
    ) {}
}
