<?php

namespace App\Support;

use App\Models\Post;
use Illuminate\Support\Facades\Log;

class PostContentNormalizer
{
    /**
     * Safely resolve a translatable attribute to a string.
     *
     * For the 'content' attribute, Tiptap editor sometimes persists state as
     * ProseMirror JSON (array or JSON-encoded string) instead of HTML. This
     * helper converts JSON → HTML transparently at read time.
     *
     * For non-content attributes, arrays are treated as corruption and logged.
     */
    public static function resolve(Post $post, string $attribute, string $locale): string
    {
        $value = $post->getTranslation($attribute, $locale, false);

        if ($value === null || $value === '') {
            return '';
        }

        if ($attribute === 'content') {
            return self::contentToHtml($value, $post->id, $locale);
        }

        if (is_array($value)) {
            Log::warning('Non-content translatable stored as array', [
                'post_id' => $post->id,
                'attribute' => $attribute,
                'locale' => $locale,
            ]);
            return '';
        }

        return is_string($value) ? $value : (string) $value;
    }

    /**
     * Convert a value that may be a Tiptap JSON document (array or JSON string)
     * to HTML. HTML strings pass through unchanged.
     */
    public static function contentToHtml(mixed $value, int|string|null $postId = null, ?string $locale = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_array($value)) {
            return self::tryConvert($value, $postId, $locale);
        }

        if (is_string($value) && self::looksLikeTiptapDoc($value)) {
            return self::tryConvert($value, $postId, $locale);
        }

        return is_string($value) ? $value : (string) $value;
    }

    /**
     * Detect whether a string is a Tiptap/ProseMirror document (JSON with type=doc root).
     * More robust than str_starts_with — handles BOM, whitespace, pretty-printed JSON.
     */
    public static function looksLikeTiptapDoc(string $raw): bool
    {
        $stripped = ltrim($raw, " \t\n\r\0\x0B\xEF\xBB\xBF");
        if (! str_starts_with($stripped, '{')) {
            return false;
        }

        $decoded = json_decode($stripped, true);
        return is_array($decoded) && ($decoded['type'] ?? null) === 'doc';
    }

    private static function tryConvert(mixed $value, int|string|null $postId, ?string $locale): string
    {
        try {
            return tiptap_converter()->asHTML($value);
        } catch (\Throwable $e) {
            Log::warning('tiptap_converter failed to render content', [
                'post_id' => $postId,
                'locale' => $locale,
                'error' => $e->getMessage(),
            ]);
            return is_string($value) ? $value : '';
        }
    }
}
