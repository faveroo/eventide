<?php

namespace App\Data\Project;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class ResponseProjectData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public bool $active,
        public ?string $check_status_url,
        public string $base_url,
        public ?Carbon $deleted_at,
        public ManagerData $manager_membership
    ) {}
}