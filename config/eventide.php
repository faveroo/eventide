<?php

return [
    'queue' => env('EVENTIDE_QUEUE', 'default'),
    'event_threshold' => 5,
    'event_window_seconds' => 300,
    'health_failure_threshold' => 3,
    'health_timeout_seconds' => 10,
    'health_slow_ms' => 2000,
    'health_interval_seconds' => 60,
    'health_stale_seconds' => 300,
];
