<?php

return [
    'active_threshold' => env('MONITOREO_ACTIVE_THRESHOLD', 5),
    'inactive_threshold' => env('MONITOREO_INACTIVE_THRESHOLD', 30),
    'location_interval' => env('MONITOREO_LOCATION_INTERVAL', 2),
    'save_location_history' => env('MONITOREO_SAVE_HISTORY', false),
];