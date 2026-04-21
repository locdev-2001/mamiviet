<?php

namespace App\Support;

use Stevebauman\Purify\Facades\Purify;

class HtmlSanitizer
{
    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return Purify::config('blog')->clean($html);
    }

    public static function normalizeMediaUrls(string $html): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl === '') {
            return $html;
        }

        return str_replace($appUrl . '/storage/', '/storage/', $html);
    }
}
