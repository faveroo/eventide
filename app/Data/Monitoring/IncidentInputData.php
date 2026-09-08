<?php

namespace App\Data\Monitoring;

use Spatie\LaravelData\Data;

class IncidentInputData extends Data
{
    public function __construct(public string $title, public string $severity) {}
}
