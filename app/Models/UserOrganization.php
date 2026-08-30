<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class UserOrganization extends Pivot
{
    use HasRoles, SoftDeletes;

    public $incrementing = true;

    protected string $guard_name = 'web';

    protected $hidden = [
        'organization_id',
        'user_id',
        'role_id',
    ];

    protected $table = 'user_organization';

    protected $fillable = [
        'user_id',
        'organization_id',
        'role_id',
    ];

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
     * Summary of user
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Summary of role
     *
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
