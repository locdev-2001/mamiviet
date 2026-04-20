<?php

namespace App\Console\Commands;

use App\Services\Admin\InstagramScraperService;
use App\Jobs\ScrapeInstagramPostsJob;
use Illuminate\Console\Command;
use Exception;

class ScrapeInstagramCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instagram:scrape {--async : Run scraping in background queue} {--retries=3 : Number of retry attempts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape Instagram posts and save to database';

    /**
     * Execute the console command.
     */
    public function handle(InstagramScraperService $service)
    {
        $async = $this->option('async');
        $retries = (int) $this->option('retries');

        if ($async) {
            return $this->handleAsync();
        } else {
            return $this->handleSync($service, $retries);
        }
    }

    /**
     * Handle synchronous scraping
     */
    private function handleSync(InstagramScraperService $service, int $retries)
    {
        $this->info('Starting Instagram scraping (synchronous mode)...');
        
        try {
            $result = $service->scrapeAndSavePosts($retries);
            
            $this->info('Instagram scraping completed successfully!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Posts', $result['total_posts']],
                    ['Saved Posts', $result['saved_count']],
                    ['Skipped Posts', $result['skipped_count']],
                    ['Errors', count($result['errors'])]
                ]
            );

            if (!empty($result['errors'])) {
                $this->warn('Errors occurred:');
                foreach ($result['errors'] as $error) {
                    $this->error($error);
                }
            }

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error('Instagram scraping failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Handle asynchronous scraping
     */
    private function handleAsync()
    {
        $this->info('Starting Instagram scraping (asynchronous mode)...');
        
        try {
            ScrapeInstagramPostsJob::dispatch();
            
            $this->info('Instagram scraping job dispatched to queue.');
            $this->info('You can monitor the progress in the logs or job queue.');
            $this->comment('Run: php artisan queue:work to process the job');

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error('Failed to dispatch Instagram scraping job: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
