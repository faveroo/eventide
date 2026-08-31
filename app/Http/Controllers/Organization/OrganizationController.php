<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\CreateOrganizationAction;
use App\Actions\Organization\DeleteOrganizationAction;
use App\Data\Organization\ResponseOrganizationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
        $active = $request->string('active', 'true')->toString();
        $withInactiveOrganizations = in_array($active, ['false', 'both'], true);

        $organizations = $user
            ->organizationMemberships()
            ->with([
                'organization' => function (Relation $relation) use ($withInactiveOrganizations): void {
                    $relation
                        ->getQuery()
                        ->when(
                            $withInactiveOrganizations,
                            fn (Builder $query) => $query->withoutGlobalScope(SoftDeletingScope::class)
                        )
                        ->with('owner');
                },
                'role',
            ])
            ->whereHas('organization', function (Builder $query) use ($active, $request): void {
                match ($active) {
                    'both' => $query->withoutGlobalScope(SoftDeletingScope::class),
                    'false' => $query
                        ->withoutGlobalScope(SoftDeletingScope::class)
                        ->where('organizations.active', false),
                    default => $query->where('organizations.active', true),
                };

                $query
                    ->when(
                        $request->filled('slug'),
                        fn (Builder $query) => $query->where(
                            'organizations.slug',
                            $request->string('slug')->toString()
                        )
                    )
                    ->when(
                        $request->filled('name'),
                        fn (Builder $query) => $query->where(
                            'organizations.name',
                            $request->string('name')->toString()
                        )
                    );
            })
            ->paginate(3);

        $organizations->through(
            fn ($organization) => ResponseOrganizationData::fromMembership($organization)
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

        $organization->load('memberships.role.permissions:id,name', 'memberships.user:id,name,email', 'owner', 'projects')->get();

        return response()->json($organization);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Organization $orgnaization): Response
    {
        return Inertia::render('organization/Edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization): mixed
    {
        Gate::authorize('update', $organization);

        $name = $request->validated('name');

        return response()->json('You are be able to update this organization');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organization $organization): JsonResponse
    {
        Gate::authorize('delete', $organization);

        $deleted = $organization->delete($organization) ? 'Deleted' : 'Not Deleted';

        return response()->json($deleted);

    }

    public function restore(Organization $organization): JsonResponse
    {
        Gate::authorize('restore', $organization);

        $restored = $organization->restore() ? 'Restored' : 'Not Restored';

        return response()->json($restored);
    }
}
