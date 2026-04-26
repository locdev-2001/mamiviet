<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate sitemap.xml with hreflang alternates for all locales';

    public function handle(): int
    {
        $base = rtrim(config('app.url'), '/');

        $pages = [
            ['de' => '/', 'en' => '/en', 'priority' => 1.0, 'frequency' => Url::CHANGE_FREQUENCY_WEEKLY],
            ['de' => '/bilder', 'en' => '/en/gallery', 'priority' => 0.8, 'frequency' => Url::CHANGE_FREQUENCY_WEEKLY],
            ['de' => '/ueber-uns', 'en' => '/en/about', 'priority' => 0.8, 'frequency' => Url::CHANGE_FREQUENCY_MONTHLY],
            ['de' => '/blog', 'en' => '/en/blog', 'priority' => 0.9, 'frequency' => Url::CHANGE_FREQUENCY_DAILY],
        ];

        $sitemap = Sitemap::create();

        foreach ($pages as $page) {
            foreach (['de', 'en'] as $locale) {
                $url = Url::create($base . $page[$locale])
                    ->setPriority($page['priority'])
                    ->setChangeFrequency($page['frequency']);

                foreach (['de', 'en'] as $altLocale) {
                    $url->addAlternate($base . $page[$altLocale], $altLocale);
                }

                $sitemap->add($url);
            }
        }

        Post::query()
            ->published()
            ->select(['id', 'slug', 'updated_at', 'created_at'])
            ->lazy(500)
            ->each(function (Post $post) use ($sitemap, $base) {
                $slugDe = $post->getTranslation('slug', 'de', false);
                $slugEn = $post->getTranslation('slug', 'en', false);

                $altPaths = [];
                if (is_string($slugDe) && $slugDe !== '') {
                    $altPaths['de'] = "/blog/{$slugDe}";
                }
                if (is_string($slugEn) && $slugEn !== '') {
                    $altPaths['en'] = "/en/blog/{$slugEn}";
                }

                $lastMod = $post->updated_at ?? $post->created_at;

                foreach ($altPaths as $locale => $path) {
                    $url = Url::create($base . $path)
                        ->setPriority(0.7)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY);

                    if ($lastMod) {
                        $url->setLastModificationDate($lastMod);
                    }

                    foreach ($altPaths as $altLocale => $altPath) {
                        $url->addAlternate($base . $altPath, $altLocale);
                    }

                    $sitemap->add($url);
                }
            });

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated at public/sitemap.xml');
        return self::SUCCESS;
    }
}
