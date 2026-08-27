<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $organizations = $request->user()->organizationMemberships()->with(['organization', 'role'])->get();

        return response()->json($organizations);
    }
}
