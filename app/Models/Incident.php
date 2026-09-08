<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['opened_at' => 'datetime', 'last_seen_at' => 'datetime', 'resolved_at' => 'datetime', 'event_count' => 'integer'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<DetectionRule, $this> */
    public function detectionRule(): BelongsTo
    {
        return $this->belongsTo(DetectionRule::class);
    }

    /** @return HasMany<AppEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(AppEvent::class);
    }
}
