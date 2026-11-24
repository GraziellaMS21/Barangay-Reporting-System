<?php

return [
    'enabled' => env('VOICE_ENABLED', true),
    'max_seconds' => env('VOICE_MAX_SECONDS', 60),
    'audio_retention_days' => env('VOICE_RETENTION_DAYS', 90),
    'demo_sample_enabled' => env('VOICE_DEMO_SAMPLE_ENABLED', false),
    'mock_transcription' => env('VOICE_MOCK_TRANSCRIPTION', false),
];
