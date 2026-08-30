<?php

namespace App\Models;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property string $name
 * @property string|null $slug
 * @property string $guard_name
 */
class Role extends SpatieRole
{
    protected $hidden = [
        'guard_name',
        'updated_at',
    ];

    protected $fillable = [
        'name',
        'slug',
        'guard_name',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $role): void {
            $role->slug ??= Str::slug($role->name);
        });
    }
}
