<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PostController extends Controller
{
    public function preview(Request $request, Post $post): Response
    {
        $requested = (string) $request->query('locale', Post::PRIMARY_LOCALE);
        $locale = in_array($requested, Post::LOCALES, true) ? $requested : Post::PRIMARY_LOCALE;
        app()->setLocale($locale);

        $title = (string) ($post->getTranslation('title', $locale, false)
            ?: $post->getTranslation('title', Post::PRIMARY_LOCALE, false)
            ?: 'Preview');

        $content = (string) ($post->getTranslation('content', $locale, false)
            ?: $post->getTranslation('content', Post::PRIMARY_LOCALE, false)
            ?: '');

        $html = view('previews.post', [
            'locale' => $locale,
            'title' => $title,
            'content' => $content,
            'post' => $post,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
