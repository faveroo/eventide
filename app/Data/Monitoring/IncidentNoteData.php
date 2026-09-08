<?php

namespace App\Data\Monitoring;

use App\Data\Auth\AuthenticatedUserData;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

class IncidentNoteData extends Data
{
    public function __construct(public int $id, public string $body, public CarbonInterface $created_at, public AuthenticatedUserData $user) {}
}
