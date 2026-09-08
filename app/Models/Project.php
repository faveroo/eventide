<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $hidden = [
        'project_manager_id',
        'organization_id',
        'api_token',
        'github_secret',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'active',
        'check_status_url',
        'base_url',
        'check_interval_seconds',
        'timeout_seconds',
        'failure_threshold',
        'latency_threshold_ms',
        'organization_id',
        'project_manager_id',
    ];

    protected $casts = [
        'active' => 'bool',
        'github_secret' => 'encrypted',
        'last_checked_at' => 'datetime',
        'consecutive_failures' => 'integer',
        'check_interval_seconds' => 'integer',
        'timeout_seconds' => 'integer',
        'failure_threshold' => 'integer',
        'latency_threshold_ms' => 'integer',
    ];

    /** @return HasMany<AppEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(AppEvent::class);
    }

    /** @return HasMany<HealthCheck, $this> */
    public function checks(): HasMany
    {
        return $this->hasMany(HealthCheck::class);
    }

    /** @return HasMany<HealthCheck, $this> */
    public function healthChecks(): HasMany
    {
        return $this->checks();
    }

    /** @return HasMany<Incident, $this> */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    /** @return HasMany<DetectionRule, $this> */
    public function rules(): HasMany
    {
        return $this->hasMany(DetectionRule::class);
    }

    /** @return HasMany<DetectionRule, $this> */
    public function detectionRules(): HasMany
    {
        return $this->rules();
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
