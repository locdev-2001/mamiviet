<?php

namespace App\Console\Commands;

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
            ['de' => '/', 'en' => '/en', 'priority' => 1.0],
            ['de' => '/bilder', 'en' => '/en/gallery', 'priority' => 0.8],
        ];

        $sitemap = Sitemap::create();

        foreach ($pages as $page) {
            foreach (['de', 'en'] as $locale) {
                $url = Url::create($base . $page[$locale])
                    ->setPriority($page['priority'])
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY);

                foreach (['de', 'en'] as $altLocale) {
                    $url->addAlternate($base . $page[$altLocale], $altLocale);
                }

                $sitemap->add($url);
            }
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated at public/sitemap.xml');
        return self::SUCCESS;
    }
}
