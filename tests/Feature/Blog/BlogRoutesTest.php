<?php

namespace Tests\Feature\Blog;

use App\Models\Post;
use App\Models\Setting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BlogRoutesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::firstOrCreate(['key' => 'footer.company_name'], ['value' => 'Mamiviet']);
        Setting::firstOrCreate(['key' => 'seo.blog.title'], ['value' => ['de' => 'Blog — Mamiviet', 'en' => 'Blog — Mamiviet']]);
    }

    private function createPost(array $overrides = []): Post
    {
        return Post::create(array_merge([
            'status' => 'published',
            'published_at' => now()->subDay(),
            'title' => ['de' => 'Testbeitrag', 'en' => 'Test Post'],
            'slug' => ['de' => 'testbeitrag', 'en' => 'test-post'],
            'excerpt' => ['de' => 'Ein Testbeitrag', 'en' => 'A test post'],
            'content' => ['de' => '<p>Hallo Welt</p>', 'en' => '<p>Hello world</p>'],
        ], $overrides));
    }

    public function test_blog_index_returns_200_with_seo_meta(): void
    {
        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="index, follow">', false);
        $response->assertSee('<link rel="canonical" href="http://mamiviet.net/blog">', false);
    }

    public function test_blog_index_en_uses_english_locale(): void
    {
        $response = $this->get('/en/blog');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="http://mamiviet.net/en/blog">', false);
        $response->assertSee('lang="en"', false);
    }

    public function test_blog_show_renders_post_with_article_jsonld(): void
    {
        $this->createPost();

        $response = $this->get('/blog/testbeitrag');

        $response->assertOk();
        $response->assertSee('Testbeitrag', false);
        $response->assertSee('"@type":"Article"', false);
        $response->assertSee('"inLanguage":"de"', false);
        $response->assertSee('og:type" content="article"', false);
    }

    public function test_blog_show_includes_hidden_content_div_for_crawler(): void
    {
        $this->createPost();

        $response = $this->get('/blog/testbeitrag');

        $response->assertSee('<div id="post-content-html" hidden', false);
        $response->assertSee('Hallo Welt', false);
    }

    public function test_blog_show_includes_hreflang_alternates(): void
    {
        $this->createPost();

        $response = $this->get('/blog/testbeitrag');

        $response->assertSee('hreflang="de" href="http://mamiviet.net/blog/testbeitrag"', false);
        $response->assertSee('hreflang="en" href="http://mamiviet.net/en/blog/test-post"', false);
    }

    public function test_blog_show_404_returns_noindex_meta(): void
    {
        $response = $this->get('/blog/does-not-exist');

        $response->assertStatus(404);
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_draft_post_is_not_visible_on_public_show(): void
    {
        $this->createPost(['status' => 'draft', 'published_at' => null]);

        $response = $this->get('/blog/testbeitrag');

        $response->assertStatus(404);
    }

    public function test_future_published_post_is_not_visible(): void
    {
        $this->createPost(['published_at' => now()->addDay()]);

        $response = $this->get('/blog/testbeitrag');

        $response->assertStatus(404);
    }

    public function test_paginated_blog_has_noindex_and_canonical_with_page_param(): void
    {
        for ($i = 0; $i < 13; $i++) {
            $this->createPost([
                'title' => ['de' => "Post {$i}", 'en' => "Post {$i}"],
                'slug' => ['de' => "post-{$i}", 'en' => "post-{$i}"],
                'published_at' => now()->subDays($i + 1),
            ]);
        }

        $response = $this->get('/blog?page=2');

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, follow">', false);
        $response->assertSee('<link rel="canonical" href="http://mamiviet.net/blog?page=2">', false);
    }

    public function test_slug_regex_rejects_uppercase(): void
    {
        $response = $this->get('/blog/UPPER-CASE');

        $response->assertStatus(404);
    }
}
