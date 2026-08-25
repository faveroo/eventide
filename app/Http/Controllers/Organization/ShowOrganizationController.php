<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowOrganizationController extends Controller
{
    public function __invoke(Organization $organization): Response
    {
        return Inertia::render('organization/Show', [
            'organization' => $organization
        ]);
    }
}
