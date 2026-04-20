<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    public const CACHE_KEY = 'site.settings';

    protected $table = 'settings';

    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget(self::CACHE_KEY);
        static::saved($flush);
        static::deleted($flush);
    }

    public static function all_grouped(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()
                ->get(['group', 'key', 'value'])
                ->groupBy('group')
                ->map(fn ($items) => $items->pluck('value', 'key')->toArray())
                ->toArray();
        });
    }

    public static function group(string $group): array
    {
        return self::all_grouped()[$group] ?? [];
    }

    public static function value(string $group, string $key, mixed $default = null): mixed
    {
        return self::group($group)[$key] ?? $default;
    }
}
