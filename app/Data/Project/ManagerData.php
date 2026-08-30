<?php

namespace App\Data\Project;

use App\Data\RoleData;
use App\Data\UserData;
use App\Models\UserOrganization;
use Spatie\LaravelData\Data;

class ManagerData extends Data
{
    public function __construct(
        public int $id,
        public UserData $user,
        public ?RoleData $role,
    ) {}

    public static function fromMembership(UserOrganization $membership): self
    {
        return new self(
            id: $membership->id,
            user: UserData::from($membership->user),
            role: $membership->role ? RoleData::fromModel($membership->role) : null,
        );
    }
}
