<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostApiResource;
use App\Models\Post;
use App\Models\Setting;
use App\Support\SeoBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PostController extends Controller
{
    private const PER_PAGE = 12;
    private const RELATED_LIMIT = 3;

    public function index(Request $request): View|Response
    {
        $locale = app()->getLocale();

        $posts = Post::query()
            ->published()
            ->forLocale($locale)
            ->with('media')
            ->latest('published_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $items = PostApiResource::collectionForLocale($posts, $locale);

        $seo = SeoBuilder::forPage('blog', $locale);
        $page = $posts->currentPage();
        if ($page > 1) {
            $seo['canonical'] .= '?page=' . $page;
            $seo['robots'] = 'noindex, follow';
        }

        return view('app', [
            'locale' => $locale,
            'seo' => $seo,
            'isHome' => false,
            'appContent' => [
                'locale' => $locale,
                'settings' => Setting::forLocale($locale),
                'blog' => [
                    'posts' => $items,
                    'pagination' => [
                        'current_page' => $posts->currentPage(),
                        'last_page' => $posts->lastPage(),
                        'total' => $posts->total(),
                        'per_page' => $posts->perPage(),
                    ],
                ],
            ],
            'breadcrumb' => $this->breadcrumbForIndex($locale),
        ]);
    }

    public function show(string $slug): View|Response
    {
        $locale = app()->getLocale();

        $post = Post::query()
            ->published()
            ->with('media')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, ?)) = ?", ["$.{$locale}", $slug])
            ->first();

        if (! $post) {
            return $this->renderNotFound($locale);
        }

        $postData = PostApiResource::forLocale($post, $locale);
        if ($postData === null) {
            return $this->renderNotFound($locale);
        }

        $related = Post::query()
            ->published()
            ->forLocale($locale)
            ->where('id', '!=', $post->id)
            ->with('media')
            ->latest('published_at')
            ->limit(self::RELATED_LIMIT)
            ->get();

        $content = $this->resolveStringTranslation($post, 'content', $locale);

        $seo = SeoBuilder::forPost($post, $locale);

        $plainText = trim(preg_replace('/\s+/u', ' ', strip_tags($content)) ?? '');

        return view('app', [
            'locale' => $locale,
            'seo' => $seo,
            'isHome' => false,
            'appContent' => [
                'locale' => $locale,
                'settings' => Setting::forLocale($locale),
                'blog' => [
                    'post' => $postData,
                    'related' => PostApiResource::collectionForLocale($related, $locale),
                ],
            ],
            'breadcrumb' => $this->breadcrumbForPost($postData, $locale),
            'postContent' => $content,
            'jsonLd' => ['article' => $this->articleJsonLd($post, $postData, $seo, $locale, $plainText)],
        ]);
    }

    public function preview(Request $request, Post $post): Response
    {
        $requested = (string) $request->query('locale', Post::PRIMARY_LOCALE);
        $locale = in_array($requested, Post::LOCALES, true) ? $requested : Post::PRIMARY_LOCALE;
        app()->setLocale($locale);

        $title = $this->resolveStringTranslation($post, 'title', $locale)
            ?: $this->resolveStringTranslation($post, 'title', Post::PRIMARY_LOCALE)
            ?: 'Preview';

        $content = $this->resolveStringTranslation($post, 'content', $locale)
            ?: $this->resolveStringTranslation($post, 'content', Post::PRIMARY_LOCALE);

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

    /**
     * Safely resolve a translatable attribute to string.
     * Handles edge cases where JSON column stores nested arrays or non-scalar values.
     */
    private function resolveStringTranslation(Post $post, string $attribute, string $locale): string
    {
        $value = $post->getTranslation($attribute, $locale, false);

        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            \Illuminate\Support\Facades\Log::warning("Post {$post->id} attribute {$attribute}[{$locale}] is array, coercing", [
                'sample' => array_slice($value, 0, 3, true),
            ]);
            return '';
        }

        return $value === null ? '' : (string) $value;
    }

    private function renderNotFound(string $locale): Response
    {
        $html = view('app', [
            'locale' => $locale,
            'seo' => SeoBuilder::notFound($locale),
            'isHome' => false,
            'appContent' => [
                'locale' => $locale,
                'settings' => Setting::forLocale($locale),
                'blog' => ['not_found' => true],
            ],
        ])->render();

        return response($html, 404, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    private function breadcrumbForIndex(string $locale): array
    {
        $base = rtrim((string) config('app.url'), '/');
        $homePath = $locale === 'en' ? '/en' : '/';
        $blogPath = $locale === 'en' ? '/en/blog' : '/blog';
        $homeLabel = $locale === 'en' ? 'Home' : 'Startseite';
        $blogLabel = 'Blog';

        return [
            ['name' => $homeLabel, 'url' => $base . $homePath],
            ['name' => $blogLabel, 'url' => $base . $blogPath],
        ];
    }

    private function breadcrumbForPost(array $postData, string $locale): array
    {
        $breadcrumb = $this->breadcrumbForIndex($locale);
        $breadcrumb[] = ['name' => $postData['title'], 'url' => $postData['url']];
        return $breadcrumb;
    }

    private function articleJsonLd(Post $post, array $postData, array $seo, string $locale, string $plainText = ''): array
    {
        $base = rtrim((string) config('app.url'), '/');
        $companyName = (string) (Setting::raw('footer.company_name') ?: 'Mamiviet');

        $image = $postData['cover']['hero'] ?? $postData['og_image'] ?? ($base . '/logo.png');
        $description = $seo['description'] ?: $postData['excerpt'];
        if ($description === '' && $plainText !== '') {
            $description = mb_substr($plainText, 0, 160);
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $postData['title'],
            'description' => $description,
            'articleBody' => $plainText !== '' ? $plainText : null,
            'image' => [SeoBuilder::absoluteImageUrl($image)],
            'author' => [
                '@type' => 'Organization',
                'name' => $postData['author_name'] ?: $companyName,
                'url' => $base,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $companyName,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $base . '/logo.png',
                ],
            ],
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'inLanguage' => $locale,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $seo['canonical'],
            ],
        ], fn ($v) => $v !== null && $v !== '');
    }
}
