<?php

return [
    'enabled' => env('VOICE_ENABLED', true),
    'max_seconds' => env('VOICE_MAX_SECONDS', 120),
    'audio_retention_days' => env('VOICE_RETENTION_DAYS', 90),
    'demo_sample_enabled' => env('VOICE_DEMO_SAMPLE_ENABLED', false),
    'extract_contact_from_audio' => env('VOICE_EXTRACT_CONTACT', true),
    'resident_extract_contact' => env('VOICE_RESIDENT_EXTRACT_CONTACT', false),
    'resident_recording_enabled' => env('VOICE_RESIDENT_RECORDING_ENABLED', true),
    'daily_analyze_limit' => env('VOICE_DAILY_ANALYZE_LIMIT', 100),
    'throttle_max_attempts' => env('VOICE_THROTTLE_MAX_ATTEMPTS', 3),
    'throttle_decay_minutes' => env('VOICE_THROTTLE_DECAY_MINUTES', 10),
    'mock_transcription' => env('VOICE_MOCK_TRANSCRIPTION', false),
];
