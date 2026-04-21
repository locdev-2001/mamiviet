<?php

namespace App\Http\Resources;

use App\Models\Post;
use App\Support\SeoBuilder;
use Carbon\Carbon;

class PostApiResource
{
    public static function forLocale(Post $post, string $locale): ?array
    {
        $slug = (string) ($post->getTranslation('slug', $locale, false) ?: '');
        if ($slug === '') {
            return null;
        }

        $title = (string) ($post->getTranslation('title', $locale, false) ?: '');
        $excerpt = (string) ($post->getTranslation('excerpt', $locale, false) ?: '');

        $cover = self::coverImage($post);
        $ogImage = self::ogImage($post);

        $published = $post->published_at ? Carbon::parse($post->published_at) : null;

        return [
            'id' => $post->id,
            'slug' => $slug,
            'title' => $title,
            'excerpt' => $excerpt,
            'cover' => $cover,
            'og_image' => $ogImage,
            'author_name' => self::authorName(),
            'published_at_iso' => $published?->toIso8601String(),
            'published_at_rfc2822' => $published?->toRfc2822String(),
            'published_at_display' => $published ? self::formatDate($published, $locale) : '',
            'reading_time' => (int) $post->reading_time,
            'url' => SeoBuilder::postPermalink($slug, $locale),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function collectionForLocale(iterable $posts, string $locale): array
    {
        $items = [];
        foreach ($posts as $post) {
            $data = self::forLocale($post, $locale);
            if ($data !== null) {
                $items[] = $data;
            }
        }

        return $items;
    }

    private static function coverImage(Post $post): ?array
    {
        $media = $post->getFirstMedia('cover');
        if (! $media) {
            return null;
        }

        return [
            'url' => $media->getUrl(),
            'thumb' => $media->getUrl('thumb'),
            'card' => $media->getUrl('card'),
            'hero' => $media->getUrl('hero'),
            'width' => (int) ($media->getCustomProperty('width') ?? 0),
            'height' => (int) ($media->getCustomProperty('height') ?? 0),
            'mime_type' => (string) ($media->mime_type ?? 'image/jpeg'),
            'size' => (int) ($media->size ?? 0),
        ];
    }

    private static function ogImage(Post $post): ?string
    {
        $manual = $post->og_image;
        if (is_string($manual) && $manual !== '') {
            return SeoBuilder::absoluteImageUrl($manual);
        }

        $og = $post->getFirstMediaUrl('og');
        if ($og) {
            return SeoBuilder::absoluteImageUrl($og);
        }

        $cover = $post->getFirstMediaUrl('cover', 'hero') ?: $post->getFirstMediaUrl('cover');
        return $cover ? SeoBuilder::absoluteImageUrl($cover) : null;
    }

    private static function authorName(): string
    {
        $companyName = \App\Models\Setting::raw('footer.company_name');
        return is_string($companyName) && $companyName !== '' ? $companyName : 'Mamiviet';
    }

    private static function formatDate(Carbon $date, string $locale): string
    {
        $pattern = $locale === 'en' ? 'MMMM D, YYYY' : 'D. MMMM YYYY';
        return $date->locale($locale)->isoFormat($pattern);
    }
}
