<?php

namespace Tests\Unit\Support;

use App\Support\HtmlSanitizer;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_strips_script_tags(): void
    {
        $dirty = '<p>Hello</p><script>alert("xss")</script><p>World</p>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('alert', $clean);
        $this->assertStringContainsString('<p>Hello</p>', $clean);
        $this->assertStringContainsString('<p>World</p>', $clean);
    }

    public function test_strips_javascript_href(): void
    {
        $dirty = '<a href="javascript:alert(1)">evil</a>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringNotContainsString('javascript:', $clean);
    }

    public function test_strips_onclick_attributes(): void
    {
        $dirty = '<p onclick="alert(1)">hover me</p>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringContainsString('hover me', $clean);
    }

    public function test_strips_unauthorized_iframe_src(): void
    {
        $dirty = '<iframe src="https://evil.com/phish"></iframe>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringNotContainsString('evil.com', $clean);
    }

    public function test_preserves_youtube_iframe_embed(): void
    {
        $dirty = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" allowfullscreen></iframe>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringContainsString('youtube.com/embed', $clean);
        $this->assertStringContainsString('allowfullscreen', $clean);
    }

    public function test_preserves_tiptap_youtube_wrapper_attrs(): void
    {
        $dirty = '<div data-youtube-video class="responsive"><iframe src="https://www.youtube.com/embed/abc" width="16" height="9" data-aspect-width="16" data-aspect-height="9"></iframe></div>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringContainsString('data-youtube-video', $clean);
        $this->assertStringContainsString('data-aspect-width', $clean);
        $this->assertStringContainsString('data-aspect-height', $clean);
        $this->assertStringContainsString('class="responsive"', $clean);
    }

    public function test_preserves_details_and_summary(): void
    {
        $dirty = '<details open><summary>Click</summary><p>Hidden content</p></details>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringContainsString('<details', $clean);
        $this->assertStringContainsString('<summary', $clean);
        $this->assertStringContainsString('Hidden content', $clean);
    }

    public function test_preserves_structural_blocks(): void
    {
        $dirty = '<h2>Title</h2><ul><li>Item</li></ul><blockquote>Quote</blockquote><table><tbody><tr><td>Cell</td></tr></tbody></table>';
        $clean = HtmlSanitizer::clean($dirty);

        foreach (['<h2', '<ul', '<li', '<blockquote', '<table', '<tr', '<td'] as $tag) {
            $this->assertStringContainsString($tag, $clean, "Expected tag {$tag} preserved");
        }
    }

    public function test_preserves_image_with_loading_attribute(): void
    {
        $dirty = '<img src="/storage/posts/content/x.jpg" alt="Pho" loading="lazy">';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringContainsString('src="/storage/posts/content/x.jpg"', $clean);
        $this->assertStringContainsString('alt="Pho"', $clean);
        $this->assertStringContainsString('loading="lazy"', $clean);
    }

    public function test_adds_rel_noopener_on_target_blank_links(): void
    {
        $dirty = '<a href="https://external.com" target="_blank">Link</a>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringContainsString('target="_blank"', $clean);
        $this->assertMatchesRegularExpression('/rel="[^"]*noopener/', $clean);
        $this->assertMatchesRegularExpression('/rel="[^"]*noreferrer/', $clean);
    }

    public function test_normalize_media_urls_strips_app_url_prefix(): void
    {
        $original = config('app.url');
        config(['app.url' => 'http://mamiviet.net']);

        $html = '<img src="http://mamiviet.net/storage/posts/content/abc.jpg" alt="x">';
        $normalized = HtmlSanitizer::normalizeMediaUrls($html);

        $this->assertStringContainsString('src="/storage/posts/content/abc.jpg"', $normalized);
        $this->assertStringNotContainsString('http://mamiviet.net/storage', $normalized);

        config(['app.url' => $original]);
    }

    public function test_normalize_media_urls_leaves_external_urls(): void
    {
        $original = config('app.url');
        config(['app.url' => 'http://mamiviet.net']);

        $html = '<img src="https://cdn.example.com/image.jpg">';
        $normalized = HtmlSanitizer::normalizeMediaUrls($html);

        $this->assertSame($html, $normalized);

        config(['app.url' => $original]);
    }

    public function test_clean_returns_empty_string_for_null_or_whitespace(): void
    {
        $this->assertSame('', HtmlSanitizer::clean(null));
        $this->assertSame('', HtmlSanitizer::clean(''));
        $this->assertSame('', HtmlSanitizer::clean('   '));
    }
}
