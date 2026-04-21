<?php

namespace Tests\Unit\Support;

use App\Support\SeoBuilder;
use Tests\TestCase;

class SeoBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://mamiviet.net']);
    }

    public function test_for_page_home_de_returns_full_seo_structure(): void
    {
        $seo = SeoBuilder::forPage('home', 'de');

        $this->assertArrayHasKey('title', $seo);
        $this->assertArrayHasKey('description', $seo);
        $this->assertArrayHasKey('keywords', $seo);
        $this->assertArrayHasKey('robots', $seo);
        $this->assertArrayHasKey('canonical', $seo);
        $this->assertArrayHasKey('hreflang', $seo);
        $this->assertArrayHasKey('og_image', $seo);

        $this->assertSame('http://mamiviet.net/', $seo['canonical']);
        $this->assertSame('http://mamiviet.net/', $seo['hreflang']['de']);
        $this->assertSame('http://mamiviet.net/en', $seo['hreflang']['en']);
    }

    public function test_for_page_en_uses_en_canonical(): void
    {
        $seo = SeoBuilder::forPage('home', 'en');

        $this->assertSame('http://mamiviet.net/en', $seo['canonical']);
        $this->assertStringContainsString('Vietnamese', $seo['title']);
    }

    public function test_for_page_bilder_canonical(): void
    {
        $de = SeoBuilder::forPage('bilder', 'de');
        $en = SeoBuilder::forPage('bilder', 'en');

        $this->assertSame('http://mamiviet.net/bilder', $de['canonical']);
        $this->assertSame('http://mamiviet.net/en/gallery', $en['canonical']);
    }

    public function test_for_page_blog_canonical(): void
    {
        $de = SeoBuilder::forPage('blog', 'de');
        $en = SeoBuilder::forPage('blog', 'en');

        $this->assertSame('http://mamiviet.net/blog', $de['canonical']);
        $this->assertSame('http://mamiviet.net/en/blog', $en['canonical']);
    }

    public function test_robots_defaults_to_index_follow(): void
    {
        $seo = SeoBuilder::forPage('home', 'de');
        $this->assertSame('index, follow', $seo['robots']);
    }

    public function test_og_image_falls_back_to_logo(): void
    {
        $seo = SeoBuilder::forPage('home', 'de');
        $this->assertSame('http://mamiviet.net/logo.png', $seo['og_image']);
    }

    public function test_not_found_uses_noindex_nofollow(): void
    {
        $seo = SeoBuilder::notFound('de');

        $this->assertSame('noindex, nofollow', $seo['robots']);
        $this->assertSame('http://mamiviet.net/blog', $seo['canonical']);
        $this->assertStringContainsString('Nicht gefunden', $seo['title']);
    }

    public function test_not_found_en_uses_english_title(): void
    {
        $seo = SeoBuilder::notFound('en');

        $this->assertStringContainsString('Not found', $seo['title']);
        $this->assertSame('noindex, nofollow', $seo['robots']);
    }

    public function test_post_permalink_builds_correct_url(): void
    {
        $this->assertSame('http://mamiviet.net/blog/my-post', SeoBuilder::postPermalink('my-post', 'de'));
        $this->assertSame('http://mamiviet.net/en/blog/my-post', SeoBuilder::postPermalink('my-post', 'en'));
    }

    public function test_post_url_path_respects_locale(): void
    {
        $this->assertSame('/blog/abc', SeoBuilder::postUrlPath('abc', 'de'));
        $this->assertSame('/en/blog/abc', SeoBuilder::postUrlPath('abc', 'en'));
    }

    public function test_absolute_image_url_handles_variants(): void
    {
        $this->assertSame('http://mamiviet.net/logo.png', SeoBuilder::absoluteImageUrl(null));
        $this->assertSame('http://mamiviet.net/logo.png', SeoBuilder::absoluteImageUrl(''));

        $this->assertSame('http://external.com/img.jpg', SeoBuilder::absoluteImageUrl('http://external.com/img.jpg'));
        $this->assertSame('https://cdn.example.com/img.jpg', SeoBuilder::absoluteImageUrl('https://cdn.example.com/img.jpg'));

        $this->assertSame('http://mamiviet.net/storage/posts/content/abc.jpg', SeoBuilder::absoluteImageUrl('/storage/posts/content/abc.jpg'));

        $this->assertSame('http://mamiviet.net/storage/foo.jpg', SeoBuilder::absoluteImageUrl('foo.jpg'));
    }
}
