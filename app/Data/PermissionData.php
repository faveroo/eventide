<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\Permission\Models\Permission;

class PermissionData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}

    public static function fromModel(Permission $permission): self
    {
        return new self(
            id: (int) $permission->getKey(),
            name: $permission->name,
        );
    }
}
