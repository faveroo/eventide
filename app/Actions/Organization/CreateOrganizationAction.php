<?php

namespace App\Actions\Organization;

use App\Data\Organization\StoreOrganizationData;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
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

        $organization = Organization::create($data->toArray());
        $organization
            ->members()
            ->attach($user->id, [
                'role_id' => 1,
            ]);

        return $organization;
    }
}
