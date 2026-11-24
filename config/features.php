<?php

return [
    'case_module' => env('FEATURE_CASE_MODULE', true),
    'agency_portal' => env('FEATURE_AGENCY_PORTAL', true),
    'case_referral_handoff' => env('FEATURE_CASE_REFERRAL_HANDOFF', false),
    'hearing_email_reminders' => env('FEATURE_HEARING_EMAIL_REMINDERS', false),
    'legacy_import' => env('FEATURE_LEGACY_IMPORT', false),
    'admin_turbo' => env('FEATURE_ADMIN_TURBO', true),
    'agency_turbo' => env('FEATURE_AGENCY_TURBO', true),
];
