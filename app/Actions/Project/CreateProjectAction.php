<?php

namespace App\Actions\Project;

use App\Data\Project\ProjectInputData;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateProjectAction
{
    public function handle(Organization $organization, ProjectInputData $data): Project
    {
        return DB::transaction(function () use ($organization, $data): Project {
            Organization::query()->lockForUpdate()->findOrFail($organization->id);
            $base = Str::slug($data->name) ?: 'project';
            $slug = $base;
            for ($suffix = 2; $organization->projects()->withTrashed()->where('slug', $slug)->exists(); $suffix++) {
                $slug = $base.'-'.$suffix;
            }

            return $organization->projects()->create([...$data->toArray(), 'slug' => $slug]);
        });
    }
}
