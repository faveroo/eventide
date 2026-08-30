<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrganizationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Organization $organization): Response
    {
        return $organization->members()->whereKey($user->id)->exists()
            ? Response::allow()
            : Response::deny('You do not belong to this organization.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organization $organization): Response
    {
        return $this->userHasOrganizationPermission($user, $organization, 'organizations.update')
            ? Response::allow()
            : Response::deny('You are not able to update this organization.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organization $organization): Response
    {
        return $this->userHasOrganizationPermission($user, $organization, 'organizations.delete')
            ? Response::allow()
            : Response::deny('You are not able to delete this organization.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Organization $organization): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Organization $organization): bool
    {
        return false;
    }

    private function userHasOrganizationPermission(User $user, Organization $organization, string $permission): bool
    {
        $membership = $organization
            ->memberships()
            ->with('role')
            ->where('user_id', $user->id)
            ->first();

        return $membership?->hasPermissionTo($permission) ?? false;
    }
}
