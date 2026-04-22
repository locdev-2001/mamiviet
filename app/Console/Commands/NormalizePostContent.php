<?php

namespace App\Console\Commands;

use App\Jobs\RegenerateSitemap;
use App\Models\Post;
use App\Support\PostContentNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class NormalizePostContent extends Command
{
    protected $signature = 'posts:normalize-content {--dry-run : Preview changes without writing}';

    protected $description = 'Convert Tiptap JSON (ProseMirror doc) content to HTML for existing posts';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $postsConverted = 0;
        $localesConverted = 0;
        $failed = 0;

        Post::withTrashed()->chunkById(50, function ($posts) use (&$postsConverted, &$localesConverted, &$failed, $dryRun) {
            foreach ($posts as $post) {
                try {
                    $touched = false;
                    foreach (Post::LOCALES as $locale) {
                        $raw = $post->getTranslation('content', $locale, false);
                        if ($raw === null || $raw === '') {
                            continue;
                        }

                        $isTiptapDoc = is_array($raw) || (is_string($raw) && PostContentNormalizer::looksLikeTiptapDoc($raw));
                        if (! $isTiptapDoc) {
                            continue;
                        }

                        $html = PostContentNormalizer::contentToHtml($raw, $post->id, $locale);
                        $this->line("  Post #{$post->id} [{$locale}]: " . strlen($html) . ' chars HTML');

                        if (! $dryRun) {
                            $post->setTranslation('content', $locale, $html);
                            $touched = true;
                        }
                        $localesConverted++;
                    }

                    if ($touched) {
                        $post->saveQuietly();
                        $postsConverted++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("  Post #{$post->id} FAILED: {$e->getMessage()}");
                }
            }
        });

        $this->newLine();
        $verb = $dryRun ? 'Would convert' : 'Converted';
        $this->info("{$verb}: {$postsConverted} posts ({$localesConverted} locales), Failed: {$failed}");

        if (! $dryRun && $postsConverted > 0) {
            Cache::forget('blog.feed.de');
            Cache::forget('blog.feed.en');
            RegenerateSitemap::dispatch();
            $this->info('RSS cache cleared + sitemap regenerate dispatched.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
