<?php

namespace App\Data\Project;

use Spatie\LaravelData\Data;

class ProjectSummaryData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
    ) {}
}
