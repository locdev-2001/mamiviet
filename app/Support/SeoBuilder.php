<?php

namespace App\Support;

use App\Models\Post;
use App\Models\Setting;

class SeoBuilder
{
    public const LOCALES = ['de', 'en'];
    public const DEFAULT_LOCALE = 'de';

    private const PAGE_FALLBACK = [
        'de' => [
            'home' => [
                'title' => 'Mamiviet — Vietnamesisches Restaurant Leipzig',
                'description' => 'Authentische vietnamesische Küche mitten in Leipzig.',
            ],
            'bilder' => [
                'title' => 'Bilder — Mamiviet Restaurant Leipzig',
                'description' => 'Eindrücke aus dem Restaurant Mamiviet.',
            ],
            'blog' => [
                'title' => 'Blog — Mamiviet Restaurant Leipzig',
                'description' => 'Geschichten, Rezepte und Neuigkeiten aus unserer vietnamesischen Küche.',
            ],
        ],
        'en' => [
            'home' => [
                'title' => 'Mamiviet — Vietnamese Restaurant Leipzig',
                'description' => 'Authentic Vietnamese cuisine in the heart of Leipzig.',
            ],
            'bilder' => [
                'title' => 'Gallery — Mamiviet Restaurant Leipzig',
                'description' => 'Impressions from Restaurant Mamiviet.',
            ],
            'blog' => [
                'title' => 'Blog — Mamiviet Restaurant Leipzig',
                'description' => 'Stories, recipes and news from our Vietnamese kitchen.',
            ],
        ],
    ];

    private const PAGE_PATHS = [
        'home' => ['de' => '/', 'en' => '/en'],
        'bilder' => ['de' => '/bilder', 'en' => '/en/gallery'],
        'blog' => ['de' => '/blog', 'en' => '/en/blog'],
    ];

    public static function forPage(string $pageKey, string $locale): array
    {
        $fallback = self::PAGE_FALLBACK[$locale][$pageKey] ?? self::PAGE_FALLBACK[self::DEFAULT_LOCALE][$pageKey] ?? ['title' => '', 'description' => ''];

        $title = (string) (Setting::get("seo.{$pageKey}.title", $locale) ?: $fallback['title']);
        $description = (string) (Setting::get("seo.{$pageKey}.description", $locale) ?: $fallback['description']);
        $keywords = (string) (Setting::get("seo.{$pageKey}.keywords", $locale) ?: '');

        $robotsRaw = Setting::raw("seo.{$pageKey}.robots");
        $robots = is_string($robotsRaw) && $robotsRaw !== '' ? $robotsRaw : 'index, follow';

        $ogRaw = Setting::raw("seo.{$pageKey}.og_image") ?: Setting::raw('seo.og_image');
        $ogImage = is_string($ogRaw) && $ogRaw !== '' ? $ogRaw : '/logo.png';

        $paths = self::PAGE_PATHS[$pageKey] ?? self::PAGE_PATHS['home'];
        $base = self::baseUrl();

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'robots' => $robots,
            'canonical' => $base . ($paths[$locale] ?? $paths[self::DEFAULT_LOCALE]),
            'hreflang' => [
                'de' => $base . $paths['de'],
                'en' => $base . $paths['en'],
            ],
            'og_image' => self::absoluteImageUrl($ogImage),
            'google_site_verification' => (string) (Setting::get('seo.google_site_verification') ?: ''),
        ];
    }

    public static function forPost(Post $post, string $locale): array
    {
        $titleFromSeo = (string) ($post->getTranslation('seo_title', $locale, false) ?: '');
        $title = $titleFromSeo !== ''
            ? $titleFromSeo
            : (string) ($post->getTranslation('title', $locale, false) ?: $post->getTranslation('title', self::DEFAULT_LOCALE, false) ?: 'Mamiviet Blog');

        $descFromSeo = (string) ($post->getTranslation('seo_description', $locale, false) ?: '');
        $description = $descFromSeo !== ''
            ? $descFromSeo
            : (string) ($post->getTranslation('excerpt', $locale, false) ?: '');

        if ($description === '') {
            $content = (string) ($post->getTranslation('content', $locale, false) ?: '');
            if ($content !== '') {
                $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($content)) ?? '');
                $description = mb_substr($plain, 0, 160);
            }
        }

        $keywords = (string) ($post->getTranslation('seo_keywords', $locale, false) ?: '');
        $slug = (string) ($post->getTranslation('slug', $locale, false) ?: '');

        $base = self::baseUrl();
        $canonical = $base . self::postUrlPath($slug, $locale);

        $hreflang = self::postHreflang($post, $base);

        $ogImage = self::resolvePostOgImage($post);

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'robots' => 'index, follow',
            'canonical' => $canonical,
            'hreflang' => $hreflang,
            'x_default' => $base . self::PAGE_PATHS['blog'][self::DEFAULT_LOCALE],
            'og_image' => $ogImage,
            'og_type' => 'article',
            'google_site_verification' => (string) (Setting::get('seo.google_site_verification') ?: ''),
        ];
    }

    public static function notFound(string $locale): array
    {
        $fallback = self::PAGE_FALLBACK[$locale][self::DEFAULT_LOCALE] ?? ['title' => 'Not found', 'description' => ''];
        $blogPath = self::PAGE_PATHS['blog'];
        $base = self::baseUrl();

        return [
            'title' => $locale === 'en' ? 'Not found — Mamiviet' : 'Nicht gefunden — Mamiviet',
            'description' => '',
            'keywords' => '',
            'robots' => 'noindex, nofollow',
            'canonical' => $base . ($blogPath[$locale] ?? $blogPath['de']),
            'hreflang' => [
                'de' => $base . $blogPath['de'],
                'en' => $base . $blogPath['en'],
            ],
            'og_image' => self::absoluteImageUrl('/logo.png'),
            'google_site_verification' => (string) (Setting::get('seo.google_site_verification') ?: ''),
        ];
    }

    public static function postUrlPath(string $slug, string $locale): string
    {
        return $locale === 'en' ? "/en/blog/{$slug}" : "/blog/{$slug}";
    }

    public static function postPermalink(string $slug, string $locale): string
    {
        return self::baseUrl() . self::postUrlPath($slug, $locale);
    }

    private static function postHreflang(Post $post, string $base): array
    {
        $hreflang = [];
        foreach (self::LOCALES as $loc) {
            $slug = (string) ($post->getTranslation('slug', $loc, false) ?: '');
            if ($slug !== '') {
                $hreflang[$loc] = $base . self::postUrlPath($slug, $loc);
            }
        }

        if ($hreflang === []) {
            $hreflang[self::DEFAULT_LOCALE] = $base . self::PAGE_PATHS['blog'][self::DEFAULT_LOCALE];
        }

        return $hreflang;
    }

    private static function resolvePostOgImage(Post $post): string
    {
        $manual = $post->og_image;
        if (is_string($manual) && $manual !== '') {
            return self::absoluteImageUrl($manual);
        }

        $og = $post->getFirstMediaUrl('og') ?: null;
        if ($og) {
            return self::absoluteImageUrl($og);
        }

        $cover = $post->getFirstMediaUrl('cover', 'hero') ?: $post->getFirstMediaUrl('cover');
        if ($cover) {
            return self::absoluteImageUrl($cover);
        }

        $fallback = Setting::raw('seo.og_image');
        return self::absoluteImageUrl(is_string($fallback) && $fallback !== '' ? $fallback : '/logo.png');
    }

    public static function absoluteImageUrl(?string $url): string
    {
        $url = is_string($url) ? trim($url) : '';
        if ($url === '') {
            return self::baseUrl() . '/logo.png';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        $base = self::baseUrl();
        if (str_starts_with($url, '/')) {
            return $base . $url;
        }

        return $base . '/storage/' . ltrim($url, '/');
    }

    private static function baseUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }
}
