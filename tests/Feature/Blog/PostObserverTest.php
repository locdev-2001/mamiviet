<?php

namespace Tests\Feature\Blog;

use App\Jobs\RegenerateSitemap;
use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PostObserverTest extends TestCase
{
    use DatabaseTransactions;

    private function createPost(array $overrides = []): Post
    {
        return Post::create(array_merge([
            'status' => 'published',
            'published_at' => now()->subDay(),
            'title' => ['de' => 'Observer Test', 'en' => 'Observer Test'],
            'slug' => ['de' => 'observer-test', 'en' => 'observer-test-en'],
            'content' => ['de' => '<p>x</p>', 'en' => '<p>x</p>'],
        ], $overrides));
    }

    public function test_create_published_post_dispatches_regenerate_job(): void
    {
        Queue::fake();

        $this->createPost();

        Queue::assertPushed(RegenerateSitemap::class);
    }

    public function test_touch_does_not_dispatch_regenerate(): void
    {
        $post = $this->createPost();
        $post = Post::find($post->id);

        Queue::fake();

        $post->touch();

        Queue::assertNotPushed(RegenerateSitemap::class);
    }

    public function test_changing_non_watched_field_does_not_dispatch(): void
    {
        $post = $this->createPost();
        $post = Post::find($post->id);

        Queue::fake();

        $post->reading_time = 99;
        $post->save();

        Queue::assertNotPushed(RegenerateSitemap::class);
    }

    public function test_changing_status_dispatches_regenerate(): void
    {
        $post = $this->createPost();

        Queue::fake();

        $post->status = 'draft';
        $post->save();

        Queue::assertPushed(RegenerateSitemap::class);
    }

    public function test_changing_slug_dispatches_regenerate(): void
    {
        $post = $this->createPost();

        Queue::fake();

        $post->setTranslation('slug', 'de', 'new-slug');
        $post->save();

        Queue::assertPushed(RegenerateSitemap::class);
    }

    public function test_delete_dispatches_regenerate(): void
    {
        $post = $this->createPost();

        Queue::fake();

        $post->delete();

        Queue::assertPushed(RegenerateSitemap::class);
    }

    public function test_force_delete_dispatches_regenerate(): void
    {
        $post = $this->createPost();

        Queue::fake();

        $post->forceDelete();

        Queue::assertPushed(RegenerateSitemap::class);
    }
}
