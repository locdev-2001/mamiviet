<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    public const CACHE_KEY = 'site.settings';

    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget(self::CACHE_KEY);
        static::saved($flush);
        static::deleted($flush);
    }

    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return static::pluck('value', 'key')->toArray();
        });
    }

    public static function raw(string $key, mixed $default = null): mixed
    {
        return self::map()[$key] ?? $default;
    }

    public static function get(string $key, ?string $locale = null, mixed $default = null): mixed
    {
        $value = self::raw($key);
        if ($value === null) {
            return $default;
        }

        if (is_array($value) && $locale !== null && self::isLocaleMap($value)) {
            return $value[$locale] ?? $value['de'] ?? $value['en'] ?? $default;
        }

        return $value;
    }

    public static function isLocaleMap(array $value): bool
    {
        if ($value === []) {
            return false;
        }
        foreach (array_keys($value) as $k) {
            if (! in_array($k, ['de', 'en'], true)) {
                return false;
            }
        }
        return true;
    }

    public static function forLocale(string $locale): array
    {
        return collect(self::map())
            ->mapWithKeys(fn ($value, string $key) => [
                $key => is_array($value) && self::isLocaleMap($value)
                    ? ($value[$locale] ?? $value['de'] ?? $value['en'] ?? null)
                    : $value,
            ])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->all();
    }

    public static function set(string $key, mixed $value): self
    {
        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
