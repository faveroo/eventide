<?php

namespace App\Http\Controllers\Organization;

use App\Data\Organization\ResponseOrganizationData;
use App\Data\Organization\StoreOrganizationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $organizations = $user
            ->organizations()
            ->with('owner')
            ->get()
            ->map(fn ($organization) => ResponseOrganizationData::from($organization));

        return response()->json($organizations);

        // return Inertia::render('organization/Index', [
        //     'organizations' => $organizations
        // ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('organization/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganizationRequest $request)
    {
        $payload = $request->validated();
        $slug = Str::slug($payload['name']);
        $user = request()->user();

        $data = StoreOrganizationData::from(
            $payload,
            [
                'owner_id' => $user->id,
                'slug' => $slug,
                'active' => true,
            ]
        );

        $organization = Organization::create($data->toArray())->load('members', 'owner');
        $organization->members()->attach($user->id);

        return redirect()->route(
            route: 'organization.show',
            parameters: [
                'organization' => $organization->id,
            ],
            status: 201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Organization $organization)
    {
        Gate::authorize('view', $organization);

        return response()->json($organization->members);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
