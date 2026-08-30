<?php

namespace App\Actions\Organization;

use App\Data\Organization\StoreOrganizationData;
use App\Models\Organization;
use App\Models\Role;
use App\Models\UserOrganization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrganizationAction
{
    public function store(string $name): Organization
    {
        $slug = Str::slug($name);
        $user = Auth::user();

        $data = StoreOrganizationData::from([
            'name' => $name,
            'slug' => $slug,
            'owner_id' => $user->id,
            'active' => true,
        ]);

        $ownerRole = Role::findByName('owner');

        $organization = DB::transaction(function () use ($data, $ownerRole, $user): Organization {
            $organization = Organization::create($data->toArray());
            $membership = UserOrganization::query()->create([
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'role_id' => $ownerRole->id,
            ]);

            $membership->syncRoles($ownerRole);

            return $organization;
        });

        return $organization;
    }
}
