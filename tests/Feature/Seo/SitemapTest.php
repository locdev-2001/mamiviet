<?php

namespace Tests\Feature\Seo;

use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use DatabaseTransactions;

    private function createPost(array $overrides = []): Post
    {
        return Post::create(array_merge([
            'status' => 'published',
            'published_at' => now()->subDay(),
            'title' => ['de' => 'Sitemap Test', 'en' => 'Sitemap Test'],
            'slug' => ['de' => 'sitemap-test-de', 'en' => 'sitemap-test-en'],
            'content' => ['de' => '<p>x</p>', 'en' => '<p>x</p>'],
        ], $overrides));
    }

    public function test_sitemap_generates_valid_xml_with_static_pages(): void
    {
        Artisan::call('sitemap:generate');

        $xml = file_get_contents(public_path('sitemap.xml'));
        $this->assertNotFalse($xml);

        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($xml), 'sitemap.xml must be valid XML');

        $this->assertStringContainsString('<loc>http://mamiviet.net/</loc>', $xml);
        $this->assertStringContainsString('<loc>http://mamiviet.net/en</loc>', $xml);
        $this->assertStringContainsString('<loc>http://mamiviet.net/blog</loc>', $xml);
        $this->assertStringContainsString('<loc>http://mamiviet.net/en/blog</loc>', $xml);
    }

    public function test_sitemap_includes_published_post_urls_with_hreflang(): void
    {
        $this->createPost();

        Artisan::call('sitemap:generate');
        $xml = file_get_contents(public_path('sitemap.xml'));

        $this->assertStringContainsString('/blog/sitemap-test-de', $xml);
        $this->assertStringContainsString('/en/blog/sitemap-test-en', $xml);
        $this->assertStringContainsString('hreflang="de"', $xml);
        $this->assertStringContainsString('hreflang="en"', $xml);
    }

    public function test_sitemap_excludes_draft_posts(): void
    {
        $this->createPost([
            'status' => 'draft',
            'published_at' => null,
            'slug' => ['de' => 'draft-slug', 'en' => 'draft-slug-en'],
        ]);

        Artisan::call('sitemap:generate');
        $xml = file_get_contents(public_path('sitemap.xml'));

        $this->assertStringNotContainsString('draft-slug', $xml);
    }

    public function test_sitemap_handles_post_missing_one_locale_slug(): void
    {
        $post = Post::create([
            'status' => 'published',
            'published_at' => now()->subDay(),
            'title' => ['de' => 'Nur Deutsch Post', 'en' => 'Nur Deutsch Post'],
            'slug' => ['de' => 'nur-deutsch-only', 'en' => 'to-be-removed'],
            'content' => ['de' => '<p>x</p>', 'en' => '<p>y</p>'],
        ]);

        \DB::table('posts')->where('id', $post->id)->update([
            'slug' => json_encode(['de' => 'nur-deutsch-only']),
        ]);

        Artisan::call('sitemap:generate');
        $xml = file_get_contents(public_path('sitemap.xml'));

        $this->assertStringContainsString('/blog/nur-deutsch-only', $xml);
        $this->assertStringNotContainsString('/en/blog/nur-deutsch-only', $xml);
        $this->assertStringNotContainsString('to-be-removed', $xml);
    }
}
