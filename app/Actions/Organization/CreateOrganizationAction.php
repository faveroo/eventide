<?php

namespace App\Actions\Organization;

use App\Data\Organization\OrganizationInputData;
use App\Data\Organization\StoreOrganizationData;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\UserOrganization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrganizationAction
{
    public function store(OrganizationInputData $data, User $user): Organization
    {
        return DB::transaction(function () use ($data, $user): Organization {
            $name = $data->name;
            $slug = Str::slug($name) ?: 'organization';
            $base = $slug;
            for ($suffix = 2; Organization::withTrashed()->where('slug', $slug)->exists(); $suffix++) {
                $slug = $base.'-'.$suffix;
            }
            $organization = Organization::create((new StoreOrganizationData($name, $slug, $user->id, true))->toArray());
            $role = Role::findOrCreate('owner', 'web');
            $membership = UserOrganization::create(['user_id' => $user->id, 'organization_id' => $organization->id, 'role_id' => $role->id]);
            $membership->syncRoles($role);

            return $organization;
        });
    }
}
