<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'slug',
        'owner_id',
        'active',
    ];

    protected $casts = [
        'active' => 'bool'
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_organization',
        )->using(UserOrganization::class)
         ->withPivot('role_id');
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->members()->where('users.active', true);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
