<?php

return [

    // Campus GPS geofence (coarse "student is near campus" check — catches
    // remote/off-campus BLE-token relay attempts, since BLE alone can't).
    'campus_latitude' => env('ATTENDANCE_CAMPUS_LAT'),
    'campus_longitude' => env('ATTENDANCE_CAMPUS_LNG'),
    'geofence_radius_meters' => env('ATTENDANCE_GEOFENCE_RADIUS', 150),

    // Used when a room has no rssi_threshold override.
    'default_rssi_threshold' => -75,

    // Fixed UUID shared by every classroom beacon (BEACON_UUID in the firmware's
    // beacon_config.h) — confirms the detected device is one of ours, not some
    // unrelated iBeacon nearby that happens to broadcast a matching Major.
    'beacon_uuid' => env('ATTENDANCE_BEACON_UUID', '6E400001-B5A3-F393-E0A9-E50E24DCCA9E'),

];
