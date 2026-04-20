<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Section extends Model implements HasMedia
{
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;

    protected $fillable = [
        'page_id',
        'key',
        'enabled',
        'order',
        'content',
        'data',
    ];

    public array $translatable = ['content'];

    protected $casts = [
        'data' => 'array',
        'enabled' => 'boolean',
        'order' => 'integer',
    ];

    public const KEYS = [
        'hero',
        'welcome',
        'welcome_second',
        'order',
        'reservation',
        'contact',
        'gallery_slider',
        'intro',
    ];

    private const SINGLE_MEDIA_BY_KEY = [
        'hero' => ['bg'],
        'welcome' => ['main', 'overlay'],
        'welcome_second' => ['main', 'overlay'],
        'order' => ['left', 'right'],
        'reservation' => ['image'],
        'contact' => ['image'],
    ];

    private const MULTI_MEDIA_BY_KEY = [
        'gallery_slider' => ['images'],
    ];

    private const CONVERSION_WIDTHS = [480, 768, 1280, 1920];

    private const ACCEPTED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function registerMediaCollections(): void
    {
        foreach (self::SINGLE_MEDIA_BY_KEY[$this->key] ?? [] as $collection) {
            $this->addMediaCollection($collection)
                ->singleFile()
                ->acceptsMimeTypes(self::ACCEPTED_MIME_TYPES);
        }

        foreach (self::MULTI_MEDIA_BY_KEY[$this->key] ?? [] as $collection) {
            $this->addMediaCollection($collection)
                ->acceptsMimeTypes(self::ACCEPTED_MIME_TYPES);
        }
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (self::CONVERSION_WIDTHS as $width) {
            $this->addMediaConversion("w{$width}")
                ->format('webp')
                ->quality(85)
                ->fit(Fit::Max, $width, $width)
                ->optimize()
                ->nonQueued();
        }

        $this->addMediaConversion('thumb')
            ->format('webp')
            ->quality(82)
            ->fit(Fit::Max, 400, 400)
            ->optimize()
            ->nonQueued();
    }

    public static function mediaCollectionsFor(string $key): array
    {
        return [
            'single' => self::SINGLE_MEDIA_BY_KEY[$key] ?? [],
            'multi' => self::MULTI_MEDIA_BY_KEY[$key] ?? [],
        ];
    }

    public function isMultiCollection(string $collection): bool
    {
        return in_array($collection, self::MULTI_MEDIA_BY_KEY[$this->key] ?? [], true);
    }
}
