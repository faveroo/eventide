<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $hidden = [
        'project_manager_id',
        'organization_id',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'active',
        'check_status_url',
        'base_url',
        'organization_id',
        'project_manager_id',
    ];

    protected $casts = [
        'active' => 'bool',
    ];

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
     * Summary of organization
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Summary of manager
     *
     * @return BelongsTo<UserOrganization, $this>
     */
    public function managerMembership(): BelongsTo
    {
        return $this->belongsTo(UserOrganization::class, 'project_manager_id');
    }
}
