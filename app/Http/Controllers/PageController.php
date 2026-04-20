<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;

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

        $page = Page::published()
            ->whereJsonContains('slug->de', $slugDe)
            ->first();

        $seo = $page?->getTranslation('seo', $locale) ?: [];

        $isHome = $slugDe === 'home';
        $data = [
            'locale' => $locale,
            'seo' => $this->buildSeo($seo, $slugDe, $locale),
            'isHome' => $isHome,
        ];

        if (! $isHome) {
            $data['breadcrumb'] = $this->buildBreadcrumb($slugDe, $locale);
        }

        return view('app', $data);
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

    private function buildSeo(array $pageSeo, string $slugDe, string $locale): array
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

        $fallback = $defaults[$locale] ?? $defaults['de'];
        $title = $pageSeo['title'] ?? $fallback['title'];
        $description = $pageSeo['description'] ?? $fallback['description'];

        $pathMap = [
            'home' => ['de' => '/', 'en' => '/en'],
            'bilder' => ['de' => '/bilder', 'en' => '/en/gallery'],
        ];

        $base = rtrim(config('app.url'), '/');
        $paths = $pathMap[$slugDe] ?? $pathMap['home'];

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $base . $paths[$locale],
            'hreflang' => [
                'de' => $base . $paths['de'],
                'en' => $base . $paths['en'],
            ],
            'og_image' => $this->safeUrl($pageSeo['og_image'] ?? null) ?? '/logo.png',
        ];
    }

    private function safeUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (str_starts_with($url, '/')) {
            return $url;
        }
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }
}
