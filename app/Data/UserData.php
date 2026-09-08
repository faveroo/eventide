<?php

namespace App\Data;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public CarbonInterface $created_at,
    ) {}
}
