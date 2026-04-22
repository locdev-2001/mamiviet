<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class NormalizePostContent extends Command
{
    protected $signature = 'posts:normalize-content {--dry-run : Preview changes without writing}';

    protected $description = 'Convert Tiptap JSON (ProseMirror doc) content to HTML for existing posts';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $converted = 0;
        $skipped = 0;

        Post::withTrashed()->chunkById(50, function ($posts) use (&$converted, &$skipped, $dryRun) {
            foreach ($posts as $post) {
                $needsSave = false;

                foreach (Post::LOCALES as $locale) {
                    $raw = $post->getTranslation('content', $locale, false);

                    if ($raw === null || $raw === '') {
                        continue;
                    }

                    $isJsonDoc = is_array($raw) || (is_string($raw) && str_starts_with(trim($raw), '{"type":"doc"'));
                    if (! $isJsonDoc) {
                        $skipped++;
                        continue;
                    }

                    $html = tiptap_converter()->asHTML($raw);
                    $this->line("  Post #{$post->id} [{$locale}]: " . strlen($html) . ' chars HTML');

                    if (! $dryRun) {
                        $post->setTranslation('content', $locale, $html);
                        $needsSave = true;
                    }
                    $converted++;
                }

                if ($needsSave) {
                    $post->saveQuietly();
                }
            }
        });

        $this->newLine();
        if ($dryRun) {
            $this->info("Dry run complete. Would convert: {$converted}, Skipped (already HTML): {$skipped}");
        } else {
            $this->info("Converted: {$converted}, Skipped (already HTML): {$skipped}");
        }

        return self::SUCCESS;
    }
}
