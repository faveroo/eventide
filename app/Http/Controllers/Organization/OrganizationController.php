<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\CreateOrganizationAction;
use App\Data\Organization\OrganizationInputData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\UserOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrganizationController extends Controller
{
    public function store(StoreOrganizationRequest $request, CreateOrganizationAction $action): RedirectResponse
    {
        $organization = $action->store(OrganizationInputData::from($request->validated()), $request->user());

        return redirect('/?organization='.$organization->slug)->with('message', 'Organização criada.');
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('update', $organization);
        $organization->update(OrganizationInputData::from($request->validated())->toArray());

        return back()->with('message', 'Organização atualizada.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        Gate::authorize('delete', $organization);
        $organization->update(['active' => false]);
        $organization->delete();

        return redirect('/?tab=organizations')->with('message', 'Organização arquivada.');
    }

    public function restore(Organization $organization): RedirectResponse
    {
        Gate::authorize('restore', $organization);
        $organization->restore();
        $organization->update(['active' => true]);

        return redirect('/?organization='.$organization->slug.'&tab=organizations')->with('message', 'Organização restaurada.');
    }

    public function member(Request $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('manageMembers', $organization);
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate(['email' => ['required', 'email', 'exists:users,email'], 'role' => ['required', Rule::in(['owner', 'project-manager', 'member'])]]);
        $user = User::where('email', $data['email'])->firstOrFail();
        if ((int) $organization->owner_id === (int) $user->id && $data['role'] !== 'owner') {
            throw ValidationException::withMessages(['role' => 'O proprietário principal deve manter o papel de owner.']);
        }
        DB::transaction(function () use ($organization, $user, $data) {
            $role = Role::query()->firstOrCreate(['name' => $data['role'], 'guard_name' => 'web']);
            $membership = UserOrganization::withTrashed()->firstOrNew(['organization_id' => $organization->id, 'user_id' => $user->id]);
            $membership->role()->associate($role);
            $membership->deleted_at = null;
            $membership->save();
            $membership->syncRoles($role);
        });

        return back()->with('message', 'Membro atualizado.');
    }

    public function removeMember(Organization $organization, int $userId): RedirectResponse
    {
        Gate::authorize('manageMembers', $organization);
        if ((int) $organization->owner_id === $userId) {
            throw ValidationException::withMessages(['member' => 'O proprietário principal não pode ser removido.']);
        }
        $membership = $organization->memberships()->where('user_id', $userId)->firstOrFail();
        DB::transaction(function () use ($organization, $membership) {
            $organization->projects()->where('project_manager_id', $membership->id)->update(['project_manager_id' => null]);
            $membership->syncRoles([]);
            $membership->delete();
        });

        return back()->with('message', 'Membro removido.');
    }
}
