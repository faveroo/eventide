<?php

namespace App\Data\Organization;

use Spatie\LaravelData\Data;

class MemberData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $role,
    ) {}
}
