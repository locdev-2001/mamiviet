<?php

namespace App\Models;

use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Post extends Model implements HasMedia
{
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;
    use SoftDeletes;

    public const LOCALES = ['de', 'en'];
    public const PRIMARY_LOCALE = 'de';
    public const WORDS_PER_MINUTE = 200;

    protected $fillable = [
        'status',
        'published_at',
        'title',
        'slug',
        'excerpt',
        'content',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'og_image',
        'reading_time',
    ];

    public array $translatable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'seo_title',
        'seo_description',
        'seo_keywords',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'reading_time' => 'integer',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->whereRaw(
            "JSON_UNQUOTE(JSON_EXTRACT(slug, ?)) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(slug, ?)) <> ''",
            ["$.{$locale}", "$.{$locale}"]
        );
    }

    protected static function booted(): void
    {
        static::saving(function (self $post) {
            foreach (self::LOCALES as $locale) {
                $rawContent = $post->getTranslation('content', $locale, false);

                // Tiptap editor sometimes emits JSON (ProseMirror doc) instead of HTML —
                // happens when Livewire serializes state as array during Word paste etc.
                // Normalize to HTML string regardless of source format.
                if (is_array($rawContent) || (is_string($rawContent) && str_starts_with(trim($rawContent), '{"type":"doc"'))) {
                    $rawContent = tiptap_converter()->asHTML($rawContent);
                }

                if (is_string($rawContent) && $rawContent !== '') {
                    $normalized = HtmlSanitizer::normalizeMediaUrls($rawContent);
                    $clean = HtmlSanitizer::clean($normalized);
                    $post->setTranslation('content', $locale, $clean);
                }

                $rawTitle = $post->getTranslation('title', $locale, false);
                if (is_string($rawTitle) && $rawTitle !== '') {
                    $currentSlug = $post->getTranslation('slug', $locale, false);
                    if (! is_string($currentSlug) || trim($currentSlug) === '') {
                        $post->setTranslation('slug', $locale, Str::slug($rawTitle));
                    }
                }
            }

            $primary = $post->getTranslation('content', self::PRIMARY_LOCALE, false);
            if (is_string($primary) && $primary !== '') {
                $text = trim(strip_tags($primary));
                $wordCount = $text === ''
                    ? 0
                    : count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);
                $post->reading_time = max(1, (int) ceil($wordCount / self::WORDS_PER_MINUTE));
            }

            if ($post->status === 'published' && $post->published_at === null) {
                $post->published_at = now();
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('og')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit(Fit::Crop, 400, 250)->nonQueued();
        $this->addMediaConversion('card')->fit(Fit::Crop, 800, 500)->nonQueued();
        $this->addMediaConversion('hero')->fit(Fit::Crop, 1600, 900)->nonQueued();
    }
}
