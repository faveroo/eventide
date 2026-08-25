<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class CreateOrganizationController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('organization/Create');
    }
}
