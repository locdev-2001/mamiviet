<?php

namespace App\Services\Admin;

use App\Models\InstagramPost;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class InstagramScraperService
{
    /**
     * Scrape Instagram posts and save to database with retry mechanism
     *
     * @param int $maxRetries
     * @return array
     */
    public function scrapeAndSavePosts($maxRetries = 3)
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::info("Instagram scraping attempt {$attempt}/{$maxRetries}");

                // Lấy cấu hình từ bảng settings
                $instagramConfig = $this->getInstagramConfig();

                if (!$instagramConfig['username'] || !$instagramConfig['token']) {
                    throw new Exception('Instagram configuration not found in settings');
                }

                // Gọi Instagram API với timeout và retry
                $posts = $this->callInstagramAPIWithRetry($instagramConfig, $attempt);

                // Lưu posts vào database
                $result = $this->savePosts($posts);

                Log::info("Instagram scraping successful", $result);
                return $result;

            } catch (Exception $e) {
                $lastException = $e;
                
                Log::warning("Instagram scraping attempt {$attempt} failed", [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries
                ]);

                if ($attempt < $maxRetries) {
                    // Exponential backoff: 5s, 10s, 20s
                    $waitTime = 5 * pow(2, $attempt - 1);
                    Log::info("Waiting {$waitTime} seconds before retry...");
                    sleep($waitTime);
                }
            }
        }

        // All attempts failed
        Log::error("All Instagram scraping attempts failed", [
            'max_retries' => $maxRetries,
            'last_error' => $lastException->getMessage()
        ]);

        throw $lastException;
    }

    /**
     * Lấy cấu hình Instagram từ bảng settings
     *
     * @return array
     */
    private function getInstagramConfig()
    {
        return [
            'username' => (string) (Setting::get('instagram.username') ?? env('INSTAGRAM_USERNAME', '')),
            'token' => (string) (Setting::get('instagram.token') ?? env('INSTAGRAM_API_TOKEN', '')),
        ];
    }

    /**
     * Gọi Instagram Scraper API với retry mechanism và extended timeout
     *
     * @param array $config
     * @param int $attempt
     * @return array
     */
    private function callInstagramAPIWithRetry($config, $attempt = 1)
    {
        // Tăng timeout dần theo attempt: 120s, 180s, 240s
        $timeout = 120 + (60 * ($attempt - 1));
        
        Log::info("Calling Instagram API", [
            'username' => $config['username'],
            'attempt' => $attempt,
            'timeout' => $timeout
        ]);

        try {
            // Sử dụng async API thay vì sync để tránh timeout
            $runId = $this->startAsyncRun($config, $timeout);
            
            if ($runId) {
                return $this->getRunResults($config['token'], $runId, $timeout);
            }

            // Fallback to sync API nếu async không hoạt động
            return $this->callSyncAPI($config, $timeout);

        } catch (Exception $e) {
            Log::error("Instagram API call failed", [
                'attempt' => $attempt,
                'timeout' => $timeout,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Start async run with Apify
     *
     * @param array $config
     * @param int $timeout
     * @return string|null
     */
    private function startAsyncRun($config, $timeout)
    {
        try {
            $url = 'https://api.apify.com/v2/acts/apify~instagram-post-scraper/runs?token=' . $config['token'];

            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post($url, [
                    'username' => [$config['username']],
                    'resultsLimit' => 1000
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['id'] ?? null;
            }

            Log::warning("Failed to start async run", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (Exception $e) {
            Log::warning("Async run start failed, will try sync", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get results from async run
     *
     * @param string $token
     * @param string $runId
     * @param int $timeout
     * @return array
     */
    private function getRunResults($token, $runId, $timeout)
    {
        $maxWaitTime = $timeout;
        $waitedTime = 0;
        $checkInterval = 10; // Check every 10 seconds

        Log::info("Waiting for async run to complete", [
            'run_id' => $runId,
            'max_wait' => $maxWaitTime
        ]);

        while ($waitedTime < $maxWaitTime) {
            try {
                // Check run status
                $statusUrl = "https://api.apify.com/v2/actor-runs/{$runId}?token={$token}";
                $statusResponse = Http::timeout(30)->get($statusUrl);

                if ($statusResponse->successful()) {
                    $status = $statusResponse->json();
                    $runStatus = $status['data']['status'] ?? 'UNKNOWN';

                    Log::info("Run status check", [
                        'run_id' => $runId,
                        'status' => $runStatus,
                        'waited_time' => $waitedTime
                    ]);

                    if ($runStatus === 'SUCCEEDED') {
                        // Get dataset items
                        $datasetId = $status['data']['defaultDatasetId'];
                        $dataUrl = "https://api.apify.com/v2/datasets/{$datasetId}/items?token={$token}";
                        
                        $dataResponse = Http::timeout(60)->get($dataUrl);
                        
                        if ($dataResponse->successful()) {
                            Log::info("Successfully retrieved async run results");
                            return $dataResponse->json();
                        }
                    } elseif (in_array($runStatus, ['FAILED', 'ABORTED', 'TIMED-OUT'])) {
                        throw new Exception("Run failed with status: {$runStatus}");
                    }
                }

                sleep($checkInterval);
                $waitedTime += $checkInterval;

            } catch (Exception $e) {
                Log::error("Error checking run status", [
                    'run_id' => $runId,
                    'error' => $e->getMessage()
                ]);
                
                if ($waitedTime > $maxWaitTime / 2) {
                    // If we've waited more than half the time, give up
                    throw $e;
                }
                
                sleep($checkInterval);
                $waitedTime += $checkInterval;
            }
        }

        throw new Exception("Async run timed out after {$maxWaitTime} seconds");
    }

    /**
     * Fallback sync API call với extended timeout
     *
     * @param array $config
     * @param int $timeout
     * @return array
     */
    private function callSyncAPI($config, $timeout)
    {
        Log::info("Using fallback sync API", ['timeout' => $timeout]);

        $url = 'https://api.apify.com/v2/acts/apify~instagram-post-scraper/run-sync-get-dataset-items?token=' . $config['token'];

        $response = Http::timeout($timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])
            ->post($url, [
                'username' => [$config['username']],
                'resultsLimit' => 500  // Reduce limit for sync API to avoid timeout
            ]);

        if (!$response->successful()) {
            throw new Exception('Failed to call Instagram sync API: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Lưu posts vào bảng instagram_posts
     *
     * @param array $posts
     * @return array
     */
    private function savePosts($posts)
    {
        $savedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($posts as $post) {
            try {
                // Check duplicate bằng short_code
                $existingPost = InstagramPost::where('short_code', $post['shortCode'] ?? null)->first();

                if ($existingPost) {
                    $skippedCount++;
                    continue;
                }

                // Tạo Instagram post mới với tất cả dữ liệu từ API response
                InstagramPost::create([
                    'type' => $post['type'] ?? null,
                    'short_code' => $post['shortCode'] ?? null,
                    'caption' => $post['caption'] ?? null,
                    'hashtags' => $post['hashtags'] ?? null,
                    'mentions' => $post['mentions'] ?? null,
                    'url' => $post['url'] ?? null,
                    'comments_count' => $post['commentsCount'] ?? null,
                    'first_comment' => $post['firstComment'] ?? null,
                    'latest_comments' => $post['latestComments'] ?? null,
                    'dimensions_height' => $post['dimensionsHeight'] ?? null,
                    'dimensions_width' => $post['dimensionsWidth'] ?? null,
                    'display_url' => $post['displayUrl'] ?? null,
                    'images' => $post['images'] ?? null,
                    'alt' => $post['alt'] ?? null,
                    'likes_count' => $post['likesCount'] ?? null,
                    'timestamp' => isset($post['timestamp']) ? \Carbon\Carbon::parse($post['timestamp']) : null,
                    'child_posts' => $post['childPosts'] ?? null,
                    'owner_full_name' => $post['ownerFullName'] ?? null,
                    'owner_username' => $post['ownerUsername'] ?? null,
                    'owner_id' => $post['ownerId'] ?? null,
                    'is_comments_disabled' => $post['isCommentsDisabled'] ?? null,
                    'input_url' => $post['inputUrl'] ?? null,
                    'is_sponsored' => $post['isSponsored'] ?? null
                ]);

                $savedCount++;
            } catch (\Exception $e) {
                $errors[] = 'Error saving post ' . ($post['shortCode'] ?? 'unknown') . ': ' . $e->getMessage();
            }
        }

        return [
            'total_posts' => count($posts),
            'saved_count' => $savedCount,
            'skipped_count' => $skippedCount,
            'errors' => $errors
        ];
    }

}
