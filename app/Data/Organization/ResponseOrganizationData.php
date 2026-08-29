<?php

namespace App\Data\Organization;

use App\Data\RoleData;
use App\Models\UserOrganization;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

class ResponseOrganizationData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public bool $active,
        public OwnerData $owner,
        public RoleData $role,
        public ?CarbonInterface $deleted_at,
        public ?CarbonInterface $created_at,
    ) {}

    public static function fromMembership(UserOrganization $membership): self
    {
        $organization = $membership->organization;

        return new self(
            id: $organization->id,
            name: $organization->name,
            slug: $organization->slug,
            active: $organization->active,
            owner: OwnerData::from($organization->owner),
            role: RoleData::from($membership->role),
            deleted_at: $organization->deleted_at,
            created_at: $organization->created_at,
        );
    }
}
