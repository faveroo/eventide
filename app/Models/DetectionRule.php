<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetectionRule extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['threshold' => 'integer', 'window_seconds' => 'integer', 'enabled' => 'boolean'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
