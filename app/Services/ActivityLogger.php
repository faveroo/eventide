<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Summary of log
     *
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        string $type,
        ?Model $subject = null,
        ?int $organizationId = null,
        ?int $projectId = null,
        ?int $userId = null,
        array $metadata = [],
    ): Activity {
        $activity = new Activity([
            'type' => $type,
            'organization_id' => $organizationId,
            'project_id' => $projectId,
            'user_id' => $userId,
            'metadata' => $metadata,
        ]);

        if ($subject) {
            $activity->subject()->associate($subject);
        }

        $activity->save();

        return $activity;
    }
}
