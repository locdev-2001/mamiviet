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

    private function renderPage(string $slugDe): View
    {
        $locale = App::getLocale();
        $isHome = $slugDe === 'home';
        $pageKey = $slugDe === 'bilder' ? 'bilder' : 'home';

        $page = Page::when($isHome, fn ($q) => $q->with('sections.media'))
            ->whereJsonContains('slug->de', $slugDe)
            ->first();

        $data = [
            'locale' => $locale,
            'seo' => SeoBuilder::forPage($pageKey, $locale),
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
}
