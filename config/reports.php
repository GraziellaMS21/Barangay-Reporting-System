<?php

return [
    'require_contact' => false,

    // Work/Resolve proof photos (card: Next step / resolution).
    'proof_photos' => [
        'max_files' => 5,
        'max_size_kb' => 5120, // 5 MB per image
        'mimes' => 'jpeg,png,webp',
        'accept' => 'image/jpeg,image/png,image/webp',
    ],

    // Referral tracking (beyond barangay authority).
    'referrals' => [
        'follow_up_days' => 3,
        'sent_via_options' => ['email', 'hand_delivered', 'phone'],
        'statuses' => ['prepared', 'sent', 'acknowledged', 'action_taken', 'closed'],
        'outcomes' => ['resolved', 'declined', 'no_response'],
        'channels' => ['phone', 'email', 'site_visit', 'other'],
    ],

    // GIS / Purok Boundary Detection configuration.
    'gis' => [
        // Algorithmic edge-proximity tolerance in meters for boundary ambiguity flagging.
        // Note: This is a geometric edge tolerance, not a claim of actual GPS device accuracy.
        'boundary_tolerance_meters' => (float) env('GIS_BOUNDARY_TOLERANCE_METERS', 5.0),
        // Optional path to official Barangay Calarian outer polygon GeoJSON (provisional simplified boundary tracked in resources/gis).
        'barangay_boundary_path' => env('GIS_BARANGAY_BOUNDARY_PATH', resource_path('gis/calarian_simplified.geojson')),
    ],
];
