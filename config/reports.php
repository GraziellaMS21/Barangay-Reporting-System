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
];
