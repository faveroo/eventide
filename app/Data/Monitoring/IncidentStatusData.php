<?php

namespace App\Data\Monitoring;

use Spatie\LaravelData\Data;

class IncidentStatusData extends Data
{
    public function __construct(public string $status) {}
}
