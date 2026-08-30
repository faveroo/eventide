<?php

namespace App\Data\Project;

use App\Models\Project;
use Carbon\CarbonInterface;
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
        public ?CarbonInterface $deleted_at,
        public ManagerData $manager_membership
    ) {}

    public static function fromModel(Project $project): self
    {
        return new self(
            id: $project->id,
            name: $project->name,
            slug: $project->slug,
            description: $project->description,
            active: $project->active,
            check_status_url: $project->check_status_url,
            base_url: $project->base_url,
            deleted_at: $project->deleted_at,
            manager_membership: ManagerData::fromMembership($project->managerMembership),
        );
    }
}
