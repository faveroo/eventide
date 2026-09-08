<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Data;

class AuthenticatedUserData extends Data
{
    public function __construct(public int $id, public string $name, public string $email) {}
}
