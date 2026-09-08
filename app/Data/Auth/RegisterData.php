<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Data;

class RegisterData extends Data
{
    public function __construct(public string $name, public string $email, public string $password) {}
}
