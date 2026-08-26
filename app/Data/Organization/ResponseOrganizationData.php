<?php

namespace App\Data\Organization;

use Spatie\LaravelData\Data;

class ResponseOrganizationData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public bool $active,
        public OwnerData $owner,
        public ?string $deleted_at,
        public string $created_at,
    ) {}
}
