<?php

namespace App\Data\Project;

use App\Data\RoleData;
use App\Data\UserData;
use Spatie\LaravelData\Data;

class ManagerData extends Data
{
    public function __construct(
        public int $id,
        public UserData $user,
        public ?RoleData $role,
    ) {}
}
