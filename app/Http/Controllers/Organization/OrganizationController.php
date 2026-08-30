<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\CreateOrganizationAction;
use App\Data\Organization\ResponseOrganizationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function __construct(
        protected CreateOrganizationAction $storeAction
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $organizations = $user
            ->organizations()
            ->with('owner')
            ->when(
                $request->filled('slug'),
                fn ($query) => $query->where(
                    'slug',
                    $request->string('slug')
                )
            )
            ->when($request->filled('name'),
                fn ($query) => $query->where(
                    'name',
                    $request->string('name')
                )
            )
            ->paginate(15);

        $organizations->through(
            fn ($organization) => ResponseOrganizationData::from($organization)
        );

        return response()->json($organizations);

        // return Inertia::render('organization/Index', [
        //     'organizations' => $organizations
        // ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('organization/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $name = $request->validated('name');

        $organization = $this->storeAction->store($name);

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
    public function show(Organization $organization): JsonResponse
    {
        Gate::authorize('view', $organization);
        $organization->load('memberships.role:id,slug', 'memberships.user:id,name,email', 'owner', 'projects')->get();

        return response()->json($organization);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organization $organization): JsonResponse
    {
        Gate::authorize('delete', $organization);

        return response()->json('You are be able to delete it');
    }
}
