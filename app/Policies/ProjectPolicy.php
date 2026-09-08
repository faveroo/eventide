<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->organization !== null && $project->organization->roleFor($user) !== null;
    }

    public function create(User $user, Organization $organization): bool
    {
        return in_array($organization->roleFor($user), ['owner', 'project-manager'], true);
    }

    public function update(User $user, Project $project): bool
    {
        return $project->organization !== null && $this->create($user, $project->organization);
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->organization?->roleFor($user) === 'owner';
    }
}
