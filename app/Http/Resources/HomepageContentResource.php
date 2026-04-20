<?php

namespace App\Http\Resources;

use App\Models\Page;
use App\Models\Section;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class HomepageContentResource
{
    private const CONVERSION_WIDTHS = [480, 768, 1280, 1920];

    public static function forLocale(Page $page, string $locale): array
    {
        return $page->sections
            ->sortBy('order')
            ->mapWithKeys(fn (Section $section) => [
                $section->key => [
                    'enabled' => (bool) $section->enabled,
                    'content' => self::stripEmpty($section->getTranslation('content', $locale, false) ?? []),
                    'media' => self::mediaFor($section, $locale),
                    'data' => $section->data ?: null,
                ],
            ])
            ->all();
    }

    private static function stripEmpty(array $content): array
    {
        return array_filter($content, fn ($value) => $value !== '' && $value !== null);
    }

    private static function mediaFor(Section $section, string $locale): array
    {
        $sectionTitle = (string) ($section->getTranslation('content', $locale, false)['title'] ?? '');

        return $section->media
            ->groupBy('collection_name')
            ->mapWithKeys(function ($items, string $collection) use ($section, $locale, $sectionTitle) {
                $shaped = $items->sortBy('order_column')
                    ->map(fn (Media $media) => self::shape($media, $locale, $sectionTitle))
                    ->filter()
                    ->values();

                return [$collection => $section->isMultiCollection($collection)
                    ? $shaped->all()
                    : $shaped->first()];
            })
            ->all();
    }

    private static function shape(Media $media, string $locale, string $sectionTitleFallback): ?array
    {
        $srcset = collect(self::CONVERSION_WIDTHS)
            ->filter(fn (int $width) => $media->hasGeneratedConversion("w{$width}"))
            ->map(fn (int $width) => $media->getUrl("w{$width}") . " {$width}w")
            ->implode(', ');

        $alt = $media->getCustomProperty('alt');
        $altForLocale = is_array($alt)
            ? ($alt[$locale] ?? $alt['de'] ?? $alt['en'] ?? '')
            : (string) ($alt ?? '');

        if ($altForLocale === '') {
            $altForLocale = $sectionTitleFallback;
        }

        return [
            'src' => $media->getUrl(),
            'srcset' => $srcset,
            'type' => 'image/webp',
            'alt' => $altForLocale,
            'width' => (int) ($media->getCustomProperty('width') ?? 0),
            'height' => (int) ($media->getCustomProperty('height') ?? 0),
        ];
    }
}
