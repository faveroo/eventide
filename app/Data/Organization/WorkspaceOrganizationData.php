<?php

namespace App\Data\Organization;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Models\UserOrganization;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Data;

class WorkspaceOrganizationData extends Data
{
    /**
     * @param  array<MemberData>  $members
     * @param  array<string, bool>  $permissions
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public int $owner_id,
        public bool $active,
        public ?string $role,
        public array $members,
        public array $permissions,
    ) {}

    public static function forUser(Organization $organization, User $user): self
    {
        $organization->loadMissing('memberships.user', 'memberships.role');

        return new self(
            $organization->id, $organization->name, $organization->slug,
            $organization->owner_id, $organization->active, $organization->roleFor($user),
            $organization->memberships->map(fn (UserOrganization $membership) => new MemberData(
                $membership->user->id, $membership->user->name, $membership->user->email, $membership->role->name,
            ))->all(),
            [
                'update' => Gate::allows('update', $organization),
                'delete' => Gate::allows('delete', $organization),
                'manageMembers' => Gate::allows('manageMembers', $organization),
                'createProject' => Gate::allows('create', [Project::class, $organization]),
            ],
        );
    }
}
