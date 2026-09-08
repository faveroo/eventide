<?php

namespace App\Data\Organization;

use Spatie\LaravelData\Data;

class OrganizationInputData extends Data
{
    public function __construct(
        public string $name,
    ) {}
}
