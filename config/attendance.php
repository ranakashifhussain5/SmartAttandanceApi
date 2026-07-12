<?php

return [

    // Campus GPS geofence (coarse "student is near campus" check — catches
    // remote/off-campus BLE-token relay attempts, since BLE alone can't).
    'campus_latitude' => env('ATTENDANCE_CAMPUS_LAT'),
    'campus_longitude' => env('ATTENDANCE_CAMPUS_LNG'),
    'geofence_radius_meters' => env('ATTENDANCE_GEOFENCE_RADIUS', 150),

    // Used when a room has no rssi_threshold override.
    'default_rssi_threshold' => -75,

];
