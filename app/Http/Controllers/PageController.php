<?php

namespace App\Http\Controllers;

use App\Http\Resources\HomepageContentResource;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    public function home(): View
    {
        return $this->renderPage('home');
    }

    public function bilder(): View
    {
        return $this->renderPage('bilder');
    }

    private function renderPage(string $slugDe): View
    {
        $locale = App::getLocale();
        $isHome = $slugDe === 'home';

        $page = Page::when($isHome, fn ($q) => $q->with('sections.media'))
            ->whereJsonContains('slug->de', $slugDe)
            ->first();

        $data = [
            'locale' => $locale,
            'seo' => $this->buildSeo($slugDe, $locale),
            'isHome' => $isHome,
            'appContent' => $this->buildAppContent($page, $locale, $isHome),
        ];

        if (! $isHome) {
            $data['breadcrumb'] = $this->buildBreadcrumb($slugDe, $locale);
        }

        return view('app', $data);
    }

    private function buildAppContent(?Page $page, string $locale, bool $isHome): array
    {
        $payload = [
            'locale' => $locale,
            'settings' => Setting::forLocale($locale),
        ];

        if (! $isHome) {
            return $payload;
        }

        if (! $page) {
            Log::warning('Homepage render fallback: no page with slug "home" found — UI will use i18n fallback.');

            return $payload;
        }

        $payload['homepage'] = HomepageContentResource::forLocale($page, $locale);

        return $payload;
    }

    private function buildBreadcrumb(string $slugDe, string $locale): array
    {
        $base = rtrim(config('app.url'), '/');
        $home = $base . ($locale === 'en' ? '/en' : '/');
        $homeName = $locale === 'en' ? 'Home' : 'Startseite';

        $pageNames = [
            'bilder' => ['de' => 'Bilder', 'en' => 'Gallery'],
        ];

        return [
            ['name' => $homeName, 'url' => $home],
            ['name' => $pageNames[$slugDe][$locale] ?? ucfirst($slugDe), 'url' => $base . request()->getPathInfo()],
        ];
    }

    private function buildSeo(string $slugDe, string $locale): array
    {
        $defaults = [
            'de' => [
                'title' => 'Mamiviet — Vietnamesisches Restaurant Leipzig',
                'description' => 'Authentische vietnamesische Küche mitten in Leipzig.',
            ],
            'en' => [
                'title' => 'Mamiviet — Vietnamese Restaurant Leipzig',
                'description' => 'Authentic Vietnamese cuisine in the heart of Leipzig.',
            ],
        ];

        $pageKey = $slugDe === 'bilder' ? 'bilder' : 'home';
        $fallback = $defaults[$locale] ?? $defaults['de'];

        $title = (string) (Setting::get("seo.{$pageKey}.title", $locale) ?: $fallback['title']);
        $description = (string) (Setting::get("seo.{$pageKey}.description", $locale) ?: $fallback['description']);
        $keywords = (string) (Setting::get("seo.{$pageKey}.keywords", $locale) ?: '');
        $robotsRaw = Setting::raw("seo.{$pageKey}.robots");
        $robots = is_string($robotsRaw) && $robotsRaw !== '' ? $robotsRaw : 'index, follow';

        $ogRaw = Setting::raw("seo.{$pageKey}.og_image") ?: Setting::raw('seo.og_image');
        $ogImage = is_string($ogRaw) && $ogRaw !== '' ? $ogRaw : '/logo.png';

        $pathMap = [
            'home' => ['de' => '/', 'en' => '/en'],
            'bilder' => ['de' => '/bilder', 'en' => '/en/gallery'],
        ];

        $base = rtrim(config('app.url'), '/');
        $paths = $pathMap[$slugDe] ?? $pathMap['home'];

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'robots' => $robots,
            'canonical' => $base . $paths[$locale],
            'hreflang' => [
                'de' => $base . $paths['de'],
                'en' => $base . $paths['en'],
            ],
            'og_image' => $this->safeUrl($ogImage) ?? '/logo.png',
            'google_site_verification' => (string) (Setting::get('seo.google_site_verification') ?: ''),
        ];
    }

    private function safeUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }
        if (str_starts_with($url, '/')) {
            return $url;
        }
        if (! str_starts_with($url, 'http')) {
            return '/storage/' . ltrim($url, '/');
        }
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }
}
