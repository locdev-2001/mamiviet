<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    function setting(string $key, ?string $locale = null, mixed $default = null): mixed
    {
        return Setting::get($key, $locale, $default);
    }
}
