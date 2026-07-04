<?php

return [
    'active_threshold' => env('MONITOREO_ACTIVE_THRESHOLD', 5),
    'inactive_threshold' => env('MONITOREO_INACTIVE_THRESHOLD', 30),
    'location_interval' => env('MONITOREO_LOCATION_INTERVAL', 2),
    'save_location_history' => env('MONITOREO_SAVE_HISTORY', false),

    'history_enabled' => env('MONITOREO_HISTORY_ENABLED', true),
    'history_retention_days' => env('MONITOREO_HISTORY_RETENTION_DAYS', 7),
    'auto_cleanup_enabled' => env('MONITOREO_AUTO_CLEANUP_ENABLED', true),
];