<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use SoftDeletes;

    protected $hidden = [
        'updated_at',
    ];

    protected $fillable = [
        'name',
        'slug',
        'owner_id',
        'active',
    ];

    protected $casts = [
        'active' => 'bool',
    ];

    /**
     * Summary of members
     *
     * @return BelongsToMany<User, $this, UserOrganization>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_organization',
        )->using(UserOrganization::class)
            ->withPivot('role_id');
    }

    /**
     * Summary of activeMembers
     *
     * @return BelongsToMany<User, $this, UserOrganization>
     */
    public function activeMembers(): BelongsToMany
    {
        return $this->members()->where('users.active', true);
    }

    /**
     * Summary of memberships
     *
     * @return HasMany<UserOrganization, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(UserOrganization::class);
    }

    /**
     * Summary of owner
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Summary of activities
     *
     * @return MorphMany<Activity, $this>
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    /**
     * Summary of projects
     *
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    protected static function booted()
    {
        static::deleting(function ($organization) {
            $organization->active = false;
            $organization->projects()->delete();
            $organization->save();
        });
    }
}
