<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserOrganization extends Pivot
{
    use SoftDeletes;

    protected $table = 'user_organization';

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
