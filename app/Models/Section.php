<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

class Section extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'page_id',
        'type',
        'order',
        'title',
        'subtitle',
        'body',
        'cta_label',
        'cta_link',
        'image_path',
        'data',
    ];

    public array $translatable = ['title', 'subtitle', 'body', 'cta_label', 'cta_link'];

    protected $casts = [
        'data' => 'array',
        'order' => 'integer',
    ];

    public const TYPES = [
        'hero',
        'intro',
        'featured_dishes',
        'gallery_teaser',
        'story',
        'contact_cta',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    protected static function booted(): void
    {
        static::updating(function (self $section) {
            $original = $section->getOriginal('image_path');
            if ($original && $original !== $section->image_path) {
                Storage::disk('public')->delete($original);
            }
        });

        static::deleted(function (self $section) {
            if ($section->image_path) {
                Storage::disk('public')->delete($section->image_path);
            }
        });
    }
}
