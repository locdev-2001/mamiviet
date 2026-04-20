<?php

use App\Models\Setting;

if (!function_exists('setting'))
{
    function setting(string $group, string $key, $default = null)
    {
        return Setting::where('group', $group)->where('key', $key)->first()->value ?? $default;
    }
}
