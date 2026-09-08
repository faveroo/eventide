<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, WorkspaceController $workspace): Response
    {
        return $workspace->index($request);
    }
}
