<?php

namespace App\Data;

use App\Models\Role;
use Spatie\LaravelData\Data;

class RoleData extends Data
{
    /**
     * @param  array<int, PermissionData>  $permissions
     */
    public function __construct(
        public int $id,
        public string $slug,
        public array $permissions = [],
    ) {}

    public static function fromModel(Role $role): self
    {
        return new self(
            id: (int) $role->getKey(),
            slug: $role->slug,
            permissions: $role->relationLoaded('permissions')
                ? $role->permissions->map(fn ($permission) => PermissionData::fromModel($permission))->all()
                : [],
        );
    }
}
