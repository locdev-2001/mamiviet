<?php

namespace Tests\Feature\Blog;

use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BlogFeedTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('blog.feed.de');
        Cache::forget('blog.feed.en');
    }

    private function createPost(array $overrides = []): Post
    {
        return Post::create(array_merge([
            'status' => 'published',
            'published_at' => now()->subDay(),
            'title' => ['de' => 'Feed Test DE', 'en' => 'Feed Test EN'],
            'slug' => ['de' => 'feed-test-de', 'en' => 'feed-test-en'],
            'excerpt' => ['de' => 'Ein Feed-Test', 'en' => 'A feed test'],
            'content' => ['de' => '<p>Inhalt</p>', 'en' => '<p>Content</p>'],
        ], $overrides));
    }

    public function test_de_feed_returns_valid_rss_with_correct_headers(): void
    {
        $this->createPost();

        $response = $this->get('/blog/feed.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');

        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($response->getContent()), 'RSS must be valid XML');

        $this->assertStringContainsString('<language>de-DE</language>', $response->getContent());
        $this->assertStringContainsString('Feed Test DE', $response->getContent());
        $this->assertStringContainsString('<atom:link', $response->getContent());
    }

    public function test_en_feed_returns_english_content(): void
    {
        $this->createPost();

        $response = $this->get('/en/blog/feed.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');
        $this->assertStringContainsString('<language>en-US</language>', $response->getContent());
        $this->assertStringContainsString('Feed Test EN', $response->getContent());
    }

    public function test_feed_escapes_cdata_closing_sequence_in_title(): void
    {
        $this->createPost([
            'title' => ['de' => 'Bad ]]> title', 'en' => 'Bad ]]> title'],
            'slug' => ['de' => 'bad-title', 'en' => 'bad-title-en'],
        ]);

        $response = $this->get('/blog/feed.xml');

        $response->assertOk();

        $doc = new \DOMDocument();
        $this->assertTrue(
            $doc->loadXML($response->getContent()),
            'RSS must remain valid XML even when content contains ]]>'
        );

        $this->assertStringContainsString(']]]]><![CDATA[>', $response->getContent());
    }

    public function test_feed_is_cached_and_invalidated_on_post_save(): void
    {
        $this->createPost();

        $first = $this->get('/blog/feed.xml')->getContent();
        $this->assertStringContainsString('Feed Test DE', $first);

        $this->createPost([
            'title' => ['de' => 'Zweiter Beitrag DE', 'en' => 'Second post EN'],
            'slug' => ['de' => 'zweiter-beitrag', 'en' => 'second-post'],
        ]);

        $second = $this->get('/blog/feed.xml')->getContent();
        $this->assertStringContainsString('Zweiter Beitrag DE', $second);
    }

    public function test_feed_excludes_draft_posts(): void
    {
        $this->createPost([
            'status' => 'draft',
            'published_at' => null,
            'title' => ['de' => 'Geheim Entwurf', 'en' => 'Secret Draft'],
            'slug' => ['de' => 'entwurf', 'en' => 'draft-en'],
        ]);

        $response = $this->get('/blog/feed.xml');

        $this->assertStringNotContainsString('Geheim Entwurf', $response->getContent());
    }
}
