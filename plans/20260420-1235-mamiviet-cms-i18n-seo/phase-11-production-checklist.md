---
title: "Phase 11 — Production deployment checklist"
status: pending
priority: P1
effort: 3h
blockedBy: [10]
---

## Context Links

- Report Inertia §10 (Supervisor SSR)
- Report Filament §7 (cron production)
- Domain: https://restaurant-mamiviet.com

## Overview

Build prod, deploy, supervisord cho SSR + queue worker, .env prod, Search Console verify, submit sitemap, run Rich Results + schema validators, Bing Webmaster.

## Key Insights

- SSR sidecar Node process MUST run via supervisord — restart on crash
- Queue worker tương tự supervisord
- Cron tick 1/min cho `schedule:run`
- robots.txt + sitemap.xml URL phải public và return 200
- Search Console 2 properties: domain (DNS verify) hoặc URL prefix (HTML file/meta tag)

## Requirements

**Functional:**
- Site live ở https://restaurant-mamiviet.com
- HTTPS forced (HSTS optional)
- SSR sidecar uptime >99% (monitored)
- Queue worker processing
- Cron running

**Non-functional:** zero downtime deploy nice-to-have, không bắt buộc MVP.

## Related Code Files

**Create:**
- `/etc/supervisor/conf.d/mamiviet-ssr.conf`
- `/etc/supervisor/conf.d/mamiviet-queue.conf`
- `.env.production` template (commit `.env.example` updated)

**Modify:**
- `.env` prod (NOT committed): APP_URL, DB, QUEUE_CONNECTION=database, CACHE_STORE=database, INERTIA_SSR_ENABLED=true, INERTIA_SSR_URL=http://127.0.0.1:13714

## Deploy Steps

1. **Build assets** (CI hoặc local):
```bash
npm ci
npm run build      # client + SSR bundles
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

2. **DB migrate + seed once**:
```bash
php artisan migrate --force
php artisan db:seed --force   # only first deploy
php artisan storage:link
php artisan sitemap:generate
```

3. **supervisord SSR** (`/etc/supervisor/conf.d/mamiviet-ssr.conf`):
```ini
[program:mamiviet-ssr]
process_name=%(program_name)s
command=php /var/www/mamiviet/artisan inertia:start-ssr
autostart=true
autorestart=true
stopwaitsecs=3600
stdout_logfile=/var/log/supervisor/mamiviet-ssr.log
stderr_logfile=/var/log/supervisor/mamiviet-ssr-err.log
user=www-data
```

4. **supervisord queue** (`/etc/supervisor/conf.d/mamiviet-queue.conf`):
```ini
[program:mamiviet-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mamiviet/artisan queue:work --tries=3 --timeout=180
autostart=true
autorestart=true
numprocs=1
stdout_logfile=/var/log/supervisor/mamiviet-queue.log
user=www-data
```

5. **Reload supervisor**:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

6. **Cron** (`crontab -e -u www-data`):
```
* * * * * cd /var/www/mamiviet && php artisan schedule:run >> /dev/null 2>&1
```

7. **Web server** (Nginx example):
```nginx
server {
    listen 443 ssl http2;
    server_name restaurant-mamiviet.com;
    root /var/www/mamiviet/public;

    ssl_certificate /etc/letsencrypt/live/.../fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/.../privkey.pem;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options nosniff always;

    gzip on; gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript image/svg+xml;
    # brotli if available

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```
Plus 80 → 443 redirect.

8. **Verify health**:
```bash
curl -I https://restaurant-mamiviet.com/                   # 200
curl -I https://restaurant-mamiviet.com/en                  # 200
curl -I https://restaurant-mamiviet.com/sitemap.xml        # 200 + xml
curl https://restaurant-mamiviet.com/robots.txt
php artisan inertia:check-ssr                               # health 200
sudo supervisorctl status                                   # all RUNNING
```

## SEO Validators (sau deploy)

1. **Google Search Console**: https://search.google.com/search-console
   - Add property `restaurant-mamiviet.com` (Domain DNS TXT) hoặc URL prefix (meta tag)
   - Submit sitemap: `https://restaurant-mamiviet.com/sitemap.xml`
   - Wait 24-72h, check Coverage report

2. **Rich Results Test**: https://search.google.com/test/rich-results
   - Test `https://restaurant-mamiviet.com/` → expect Restaurant + LocalBusiness eligible
   - Test `https://restaurant-mamiviet.com/en` same

3. **Schema.org Validator**: https://validator.schema.org/
   - Paste URL, verify all schemas pass

4. **Bing Webmaster**: https://www.bing.com/webmasters
   - Add site, verify, submit sitemap

5. **PageSpeed Insights**: https://pagespeed.web.dev/
   - Mobile + Desktop, verify CWV trong threshold

## Final Smoke Tests

- [ ] HTTPS works, no mixed content warnings
- [ ] `/` DE content + lang="de"
- [ ] `/en` EN content + lang="en"
- [ ] `/admin` login works, can edit page → reload `/` shows update
- [ ] Image upload via admin → WebP visible on FE
- [ ] InstagramPost "Scrape Now" → notification + after queue worker tick, post appears
- [ ] sitemap.xml lists 4 URLs (`/`, `/en`, `/bilder`, `/en/bilder`) with hreflang
- [ ] robots.txt allows root, disallows /admin
- [ ] Rich Results Test pass
- [ ] Lighthouse mobile thresholds met (Phase 10)

## Todo List

- [ ] CI build pipeline (or manual deploy script)
- [ ] supervisord SSR config + start
- [ ] supervisord queue config + start
- [ ] Cron entry
- [ ] Nginx config + SSL cert (Let's Encrypt)
- [ ] storage:link + sitemap:generate one-time
- [ ] Search Console verify + submit sitemap
- [ ] Rich Results Test pass
- [ ] Schema validator pass
- [ ] Bing Webmaster submit
- [ ] PageSpeed Insights field check (after 28 days)
- [ ] Update NAP placeholder với real data trong admin
- [ ] Document deploy procedure trong `docs/deployment.md`

## Success Criteria

- All public URLs return 200 HTTPS
- supervisord status: SSR + queue RUNNING
- Search Console accept sitemap, no errors
- Rich Results Test pass cả 2 schemas
- Lighthouse field data CWV "Good" sau 28 ngày

## Rollback Plan

| Issue | Rollback action |
|-------|----------------|
| SSR sidecar crash loop | `supervisorctl stop mamiviet-ssr` → set `INERTIA_SSR_ENABLED=false` → app falls back client-side render |
| Migration breaks prod | `php artisan migrate:rollback --step=1` (only safe khi migration reversible — verify trong staging) |
| Sitemap broken | Delete `public/sitemap.xml`, revert command |
| Asset bundle bug | Re-deploy previous `public/build/` from backup, kiểm git tag prev release |
| Domain DNS issue | Revert DNS to maintenance page |

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| First deploy DB seed runs trên prod data hiện có (none expected) | Wrap seeders với `if (Page::count()===0)` guard |
| .env secrets leak | Strict file perms `chmod 600 .env`, never commit |
| SSR Node memory leak over time | supervisord restart daily via cron `0 3 * * * supervisorctl restart mamiviet-ssr` |
| Queue worker stale code post-deploy | supervisord `restart mamiviet-queue` trong deploy script |
| Search Console verify fail | Fallback: HTML file upload to public/ |

## Quality Loop

`/ck:code-review` deploy scripts + Nginx + supervisord configs → `/simplify` (consolidate deploy steps thành 1 shell script `deploy.sh`) → live smoke test full checklist.

## Next Steps

Project complete. Follow-up items:
- Monitor CWV field data 28 ngày → adjust nếu cần
- User fills real NAP + hours via admin
- Plan v1.1: orphan media cleanup, sitemap auto-update on Page save event, more pages (menu, contact)
