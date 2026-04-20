<?php

namespace App\Jobs;

use App\Services\Admin\InstagramScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ScrapeInstagramPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return int
     */
    public function backoff(): int
    {
        return 60; // Wait 60 seconds between retries
    }

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // Set queue name for this job
        $this->onQueue('instagram-scraping');
    }

    /**
     * Execute the job.
     */
    public function handle(InstagramScraperService $service): void
    {
        try {
            Log::info('Instagram scraping job started');
            
            $result = $service->scrapeAndSavePosts(2); // Reduce retries in job since job itself will retry
            
            Log::info('Instagram scraping job completed successfully', $result);
            
        } catch (Exception $e) {
            Log::error('Instagram scraping job failed', [
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries
            ]);
            
            // If this is the last attempt, don't retry
            if ($this->attempts() >= $this->tries) {
                Log::error('Instagram scraping job failed after all retries');
            }
            
            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Instagram scraping job failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
        
        // You could notify admins here
        // Mail::to('admin@example.com')->send(new ScrapingFailedMail($exception));
    }
}
