<?php

namespace App\Data\Monitoring;

use Spatie\LaravelData\Data;

class IncidentNoteInputData extends Data
{
    public function __construct(public string $body) {}
}
