<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = ['slug', 'status', 'seo', 'content'];

    public array $translatable = ['slug', 'seo', 'content'];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('order');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    protected static function booted(): void
    {
        static::updating(function (self $page) {
            $oldImages = self::collectOgImages(json_decode($page->getRawOriginal('seo') ?? '[]', true) ?: []);
            $newImages = self::collectOgImages(is_array($page->seo) ? $page->seo : []);
            foreach (array_diff($oldImages, $newImages) as $path) {
                if (is_string($path) && $path !== '') {
                    Storage::disk('public')->delete($path);
                }
            }
        });

        static::deleted(function (self $page) {
            foreach (self::collectOgImages(json_decode($page->getRawOriginal('seo') ?? '[]', true) ?: []) as $path) {
                if (is_string($path) && $path !== '') {
                    Storage::disk('public')->delete($path);
                }
            }
        });
    }

    private static function collectOgImages(array $seoByLocale): array
    {
        $paths = [];
        foreach ($seoByLocale as $seo) {
            if (is_array($seo) && ! empty($seo['og_image']) && is_string($seo['og_image'])) {
                $paths[] = $seo['og_image'];
            }
        }

        return $paths;
    }
}
