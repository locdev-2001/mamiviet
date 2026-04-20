---
title: "Phase 09 — Cron schedule + manual scrape button"
status: pending
priority: P2
effort: 2h
blockedBy: [07]
---

## Context Links

- Report: `plans/reports/researcher-20260420-filament-i18n-media.md` §6, §7
- Existing: `app/Jobs/ScrapeInstagramPostsJob.php` (kiểm tra exist trước)

## Overview

Schedule Job mỗi 6h `withoutOverlapping`. Manual button trong InstagramPostResource header (Phase 07 đã ref). Document Windows Task Scheduler + Linux cron.

## Key Insights

- `withoutOverlapping()` dùng cache lock — cần cache driver `database`/`redis` (file driver works locally but not concurrent-safe)
- `runInBackground()` không block scheduler tick
- Manual dispatch không cần queue worker để chạy nếu QUEUE_CONNECTION=sync (dev). Prod nên `database` queue + worker
- Windows Laragon: Task Scheduler XML cần absolute paths
- Existing job nếu đã có thì verify signature `dispatch()` (không args) compatible

## Requirements

**Functional:**
- Schedule: every 6 hours, no overlap, background
- Header action button dispatch + toast notification
- Existing job class hoạt động được; nếu chưa có, tạo skeleton (parsing logic out of scope — placeholder)

**Non-functional:** scheduler tick 1/min reliable cả Windows + Linux.

## Related Code Files

**Modify:**
- `app/Console/Kernel.php` (schedule + sitemap weekly từ Phase 06)
- `config/queue.php` (default `database` cho prod) — only docs change in .env example

**Create (nếu chưa có):**
- `app/Jobs/ScrapeInstagramPostsJob.php`
- Migration `jobs` table: `php artisan queue:table && migrate`

**Verify exists:**
- `app/Models/InstagramPost.php`
- `app/Filament/Resources/InstagramPostResource.php` (Phase 07)

## Implementation Steps

1. Verify hoặc create `ScrapeInstagramPostsJob`:
```php
namespace App\Jobs;
class ScrapeInstagramPostsJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $timeout = 120;
    public int $tries = 3;
    public function handle(): void {
        // existing scrape logic OR placeholder TODO
        info('Instagram scrape executed at ' . now());
    }
}
```

2. Console/Kernel.php:
```php
protected function schedule(Schedule $schedule): void {
    $schedule->job(new ScrapeInstagramPostsJob())
        ->everySixHours()
        ->withoutOverlapping(10)
        ->runInBackground()
        ->onFailure(fn()=>logger()->error('Instagram scrape failed'));

    $schedule->command('sitemap:generate')->weekly()->onFailure(fn()=>logger()->error('Sitemap gen failed'));
}
```

3. Queue setup:
```bash
php artisan queue:table
php artisan migrate
```
.env prod: `QUEUE_CONNECTION=database`, `CACHE_STORE=database`.

4. Scrape action button — already added Phase 07. Re-verify dispatch:
```php
Action::make('scrapeNow')
    ->action(function() {
        ScrapeInstagramPostsJob::dispatchSync(); // sync trong dev cho instant feedback
        // OR: dispatch() để qua queue prod
        Notification::make()->title('Scrape started')->success()->send();
    });
```
Recommend: `dispatch()` luôn, user expect async; toast nói "in background".

5. Cron setup docs:

**Linux production** (`crontab -e`):
```
* * * * * cd /var/www/mamiviet && php artisan schedule:run >> /dev/null 2>&1
```

**Windows / Laragon dev** — Task Scheduler:
- Action: Start program `php.exe`
- Args: `D:\Data\laragon\www\mamiviet\artisan schedule:run`
- Start in: `D:\Data\laragon\www\mamiviet`
- Trigger: At startup, repeat every 1 minute, indefinitely
- Run whether user logged in or not, hidden

**Worker (prod)**:
```
* * * * * php /var/www/mamiviet/artisan queue:work --stop-when-empty --max-time=60
```
Better: supervisord (Phase 11 docs).

6. Test:
```bash
php artisan schedule:list  # verify job listed
php artisan schedule:run   # manual tick — should dispatch
# admin click "Scrape Now" → toast + check storage/logs/laravel.log "scrape executed"
```

## Todo List

- [ ] ScrapeInstagramPostsJob exist hoặc tạo skeleton
- [ ] Schedule trong Console/Kernel
- [ ] queue:table migration
- [ ] Verify scrape action dispatch + toast
- [ ] `schedule:list` + `schedule:run` manual test
- [ ] Document cron Linux + Windows Task Scheduler
- [ ] Test cron runs every minute (watch `storage/logs`)

## Success Criteria

- `schedule:run` dispatches job; log written
- Admin button click → toast + log entry within seconds (sync) or after worker pickup (async)
- No overlap khi 2 scheduler ticks cùng lúc

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| `withoutOverlapping` không hoạt động với cache=file (local) | Switch CACHE_STORE=database trong .env |
| Job timeout instagram scrape > 60s | Set `$timeout=120`; queue worker `--timeout=180` |
| Windows Task Scheduler không trigger | Verify "Run with highest privileges", xem History tab |
| Existing job có args/signature khác | Inspect file, adjust dispatch call |
| Queue worker không chạy prod → button không effect | Phase 11 supervisord cho queue:work |

## Quality Loop

`/ck:code-review` Kernel schedule + job → `/simplify` (chuẩn hoá log helper) → manual schedule:run + button click verify.

## Next Steps

→ Phase 10 perf check không liên quan trực tiếp. → Phase 11 supervisord queue worker.
