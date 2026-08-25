<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class StoreOrganizationData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
        public int $owner_id,
        public bool $active,
    ) {}
}
