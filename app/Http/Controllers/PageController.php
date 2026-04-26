<?php

namespace App\Http\Controllers;

use App\Http\Resources\HomepageContentResource;
use App\Models\Page;
use App\Models\Setting;
use App\Support\SeoBuilder;
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

    public function about(): View
    {
        return $this->renderPage('ueber-uns');
    }

    private function renderPage(string $slugDe): View
    {
        $locale = App::getLocale();
        $isHome = $slugDe === 'home';
        $pageKey = match ($slugDe) {
            'bilder' => 'bilder',
            'ueber-uns' => 'about',
            default => 'home',
        };

        $page = Page::when($isHome, fn ($q) => $q->with('sections.media'))
            ->whereJsonContains('slug->de', $slugDe)
            ->first();

        $data = [
            'locale' => $locale,
            'seo' => SeoBuilder::forPage($pageKey, $locale, $page),
            'isHome' => $isHome,
            'appContent' => $this->buildAppContent($page, $locale, $isHome, $pageKey),
        ];

        if (! $isHome) {
            $data['breadcrumb'] = $this->buildBreadcrumb($slugDe, $locale);
        }

        return view('app', $data);
    }

    private function buildAppContent(?Page $page, string $locale, bool $isHome, string $pageKey = 'home'): array
    {
        $payload = [
            'locale' => $locale,
            'settings' => Setting::forLocale($locale),
        ];

        if ($pageKey === 'about') {
            if ($page) {
                $content = $page->getTranslation('content', $locale);
                $fallbackContent = $page->getTranslation('content', 'de');
                $heroImage = $content['hero_image'] ?? $fallbackContent['hero_image'] ?? null;

                $payload['about'] = [
                    'title' => $content['title'] ?? $fallbackContent['title'] ?? null,
                    'content' => $content['body'] ?? $fallbackContent['body'] ?? null,
                    'heroImage' => $this->publicStorageUrl($heroImage),
                ];
            }

            return $payload;
        }

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

    private function publicStorageUrl(?string $path): ?string
    {
        $path = is_string($path) ? trim($path) : '';

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return '/storage/' . ltrim($path, '/');
    }

    private function buildBreadcrumb(string $slugDe, string $locale): array
    {
        $base = rtrim(config('app.url'), '/');
        $home = $base . ($locale === 'en' ? '/en' : '/');
        $homeName = $locale === 'en' ? 'Home' : 'Startseite';

        $pageNames = [
            'bilder' => ['de' => 'Bilder', 'en' => 'Gallery'],
            'ueber-uns' => ['de' => 'Über uns', 'en' => 'About Us'],
        ];

        return [
            ['name' => $homeName, 'url' => $home],
            ['name' => $pageNames[$slugDe][$locale] ?? ucfirst($slugDe), 'url' => $base . request()->getPathInfo()],
        ];
    }
}
