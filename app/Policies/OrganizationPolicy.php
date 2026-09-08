<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Organization $organization): bool
    {
        return $organization->roleFor($user) !== null;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $organization->roleFor($user) === 'owner';
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $this->update($user, $organization);
    }

    public function restore(User $user, Organization $organization): bool
    {
        return $this->update($user, $organization);
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $this->update($user, $organization);
    }
}
