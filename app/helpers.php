<?php

use App\Services\SettingsService;

if (! function_exists('setting')) {
    /**
     * Get a setting value by key with optional default.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsService::class)->get($key, $default);
    }
}
