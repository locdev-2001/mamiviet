<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED = ['de', 'en'];
    public const DEFAULT = 'de';

    public function handle(Request $request, Closure $next): Response
    {
        $first = strtolower((string) $request->segment(1));

        if ($first === self::DEFAULT) {
            $rest = implode('/', array_slice($request->segments(), 1));
            $query = $request->getQueryString();
            $url = '/' . $rest . ($query ? '?' . $query : '');
            return redirect($url, 301);
        }

        $locale = in_array($first, self::SUPPORTED, true) ? $first : self::DEFAULT;
        App::setLocale($locale);

        return $next($request);
    }
}
