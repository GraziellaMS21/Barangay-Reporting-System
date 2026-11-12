<?php

return [
    // Default notification channels
    'default' => env('NOTIFICATIONS_CHANNEL', 'mail'),

    'channels' => [
        'mail' => [
            'driver' => 'mail',
        ],
        'slack' => [
            'driver' => 'slack',
            'url' => env('SLACK_WEBHOOK_URL'),
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'admin_notifications',
        ],
    ],
];

