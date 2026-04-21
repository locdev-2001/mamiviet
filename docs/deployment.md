# Deployment — Mamiviet

Production deploy cho **restaurant-mamiviet.com** trên VPS Ubuntu + aaPanel/BT Panel + Nginx + PHP-FPM + MySQL.

## Stack assumption

- **Server**: VPS Ubuntu 22.04+ (hoặc Debian 12+)
- **Panel**: aaPanel / BT Panel (paths bắt đầu `/www/...`)
- **Document root hiện tại**: `/www/wwwroot/restaurant-mamiviet.com`
- **Web server**: Nginx (được cài bởi panel)
- **PHP**: 8.3+ (panel install via "Software Store")
- **DB**: MySQL 8 (đã có qua panel)
- **SSL**: Let's Encrypt (đã issue qua panel)
- **Queue**: database driver (không cần Redis)
- **Node**: Bun 1.x (nếu không có bun → dùng Node 20 + `npm`)

---

## Phần 1 — First-time setup (làm 1 lần)

### 1.1 Install prerequisites trên server

```bash
# SSH vào VPS với user root hoặc sudo
ssh root@<VPS_IP>

# Install Bun (recommended)
curl -fsSL https://bun.sh/install | bash
source ~/.bashrc

# Hoặc install Node nếu không dùng Bun
# curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
# apt install -y nodejs

# Verify PHP 8.3 + extensions
php -v                 # phải >= 8.3
php -m | grep -iE 'bcmath|ctype|fileinfo|json|mbstring|openssl|pdo_mysql|tokenizer|xml|gd|intl|zip|exif'
# aaPanel → PHP Management → Install Extensions: gd, intl, zip, exif, bcmath, fileinfo, opcache

# Install Composer (nếu panel chưa có)
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

### 1.2 Database setup

aaPanel GUI → **Databases** → Add:
- DB name: `mamiviet`
- Username: `mamiviet`
- Password: (strong random, save securely)
- Access: localhost only

### 1.3 Clone repo

**aaPanel tạo sẵn thư mục** `/www/wwwroot/restaurant-mamiviet.com` khi setup website. Phải backup + xoá trước khi clone:

```bash
# Backup landing cũ (bao gồm cả thư mục mặc định của aaPanel)
mkdir -p /root/backup
mv /www/wwwroot/restaurant-mamiviet.com /root/backup/landing-old-$(date +%Y%m%d)

# Clone fresh
cd /www/wwwroot
git clone https://github.com/locdev-2001/mamiviet.git restaurant-mamiviet.com
cd restaurant-mamiviet.com

# Ownership (www là user Nginx mặc định của aaPanel)
chown -R www:www /www/wwwroot/restaurant-mamiviet.com
chmod -R 755 /www/wwwroot/restaurant-mamiviet.com
chmod -R 775 storage bootstrap/cache
```

> **Lưu ý SSL**: cert Let's Encrypt đã issue nằm tại `/www/server/panel/vhost/cert/restaurant-mamiviet.com/` (tách biệt khỏi document root) → an toàn khi xoá web root. `.well-known/` validation cũng dùng path riêng qua include.

### 1.4 Environment config

```bash
cp .env.production.example .env
nano .env
# Điền tối thiểu:
#   DB_PASSWORD=<strong>
#   APP_URL=https://restaurant-mamiviet.com
#   INSTAGRAM_USERNAME=mami.viet
#   INSTAGRAM_API_TOKEN=<apify_token>     (optional — có thể set qua Setting sau)
```

### 1.5 Install dependencies

**Quan trọng — đúng thứ tự**: composer install **TRƯỚC** `key:generate` (artisan cần `vendor/autoload.php`).

```bash
# 1. Composer dependencies trước
composer install --no-dev --optimize-autoloader

# 2. Generate APP_KEY (sau khi có vendor/)
php artisan key:generate --force

# 3. Frontend build
bun install --frozen-lockfile
bun run build
```

> **Nếu `bun install` fail** với lỗi "tarball extraction" hoặc "network timeout":
> ```bash
> rm -rf node_modules ~/.bun/install/cache
> bun install --frozen-lockfile
> ```
> **Fallback** nếu bun tiếp tục lỗi: dùng npm
> ```bash
> apt install -y nodejs npm   # hoặc curl Node 20 setup
> npm install --no-audit --no-fund
> npm run build
> ```
> Set `USE_NPM=1 ./deploy.sh` cho lần sau.

### 1.6 Publish vendor assets

Livewire + Filament asset files phải publish thủ công, nếu không sẽ 404 `/livewire/livewire.min.js` và Filament components không load:

```bash
php artisan vendor:publish --tag=livewire:assets --force
php artisan filament:assets
php artisan storage:link
```

### 1.7 Database migrate + seed

```bash
php artisan migrate --force
php artisan db:seed --class=GlobalSettingsSeeder --force
# KHÔNG seed PostSeeder (đó là demo data)

# Tạo admin user đầu tiên cho Filament
php artisan make:filament-user
# Prompt: name, email, password
```

### 1.8 Permissions cuối

```bash
chown -R www:www storage bootstrap/cache public/build public/storage public/vendor
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;
```

### 1.9 Nginx config — update cho Laravel

**2 thay đổi CRITICAL** (không làm → site không chạy):

1. **Nginx root** → trỏ vào `public/` subfolder (qua aaPanel → Websites → site → **Directory** tab → "Website directory")
2. **Laravel rewrite** → điền vào rewrite file riêng (aaPanel → **URL rewrite** tab), KHÔNG viết trực tiếp trong main config

**⚠️ Lưu ý aaPanel validator**:
- Trong main config, **GIỮ NGUYÊN** dòng `#error_page 404/404.html;` ngay sau `#SSL-START` — panel từ chối save nếu xoá
- Rewrite rule vào file riêng: `/www/server/panel/vhost/rewrite/restaurant-mamiviet.com.conf`, không paste vào main config

**Rewrite file** (`/www/server/panel/vhost/rewrite/restaurant-mamiviet.com.conf`):
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

**Main config** (`/www/server/panel/vhost/nginx/restaurant-mamiviet.com.conf`) — chỉ đổi `root` + thêm security headers + block sensitive files. Giữ nguyên các aaPanel sentinel comments:

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name restaurant-mamiviet.com www.restaurant-mamiviet.com;
    index index.php index.html;
    root /www/wwwroot/restaurant-mamiviet.com/public;

    # Redirect www → non-www
    if ($host = www.restaurant-mamiviet.com) {
        return 301 https://restaurant-mamiviet.com$request_uri;
    }

    # Force HTTPS
    if ($scheme = http) {
        return 301 https://$host$request_uri;
    }

    #CERT-APPLY-CHECK--START
    include /www/server/panel/vhost/nginx/well-known/restaurant-mamiviet.com.conf;
    #CERT-APPLY-CHECK--END

    #SSL-START SSL related configuration, do NOT delete or modify the next line of commented-out 404 rules
    #error_page 404/404.html;
        ssl_certificate    /www/server/panel/vhost/cert/restaurant-mamiviet.com/fullchain.pem;
        ssl_certificate_key    /www/server/panel/vhost/cert/restaurant-mamiviet.com/privkey.pem;
        ssl_protocols TLSv1.2 TLSv1.3;
        ssl_ciphers EECDH+CHACHA20:EECDH+AES128:EECDH+AES256:!MD5;
        ssl_prefer_server_ciphers on;
        ssl_session_tickets on;
        ssl_session_cache shared:SSL:10m;
        ssl_session_timeout 10m;
        add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
        add_header X-Content-Type-Options nosniff always;
        add_header X-Frame-Options SAMEORIGIN always;
        add_header Referrer-Policy strict-origin-when-cross-origin always;
        error_page 497  https://$host$request_uri;
    #SSL-END

    #ERROR-PAGE-START  Error page configuration, allowed to be commented, deleted or modified
    error_page 404 /404.html;
    error_page 502 /502.html;
    #ERROR-PAGE-END

    #PHP-INFO-START
    include enable-php-83.conf;
    #PHP-INFO-END

    #REWRITE-START
    include /www/server/panel/vhost/rewrite/restaurant-mamiviet.com.conf;
    #REWRITE-END

    # Block sensitive files (Laravel-specific additions)
    location ~ ^/(\.user\.ini|\.htaccess|\.git|\.env|\.svn|\.project|LICENSE|README\.md|deploy\.sh|composer\.(json|lock)|package\.json|bun\.lock)$ {
        deny all;
        return 404;
    }

    location ~ \.well-known { allow all; }

    if ( $uri ~ "^/\.well-known/.*\.(php|jsp|py|js|css|lua|ts|go|zip|tar\.gz|rar|7z|sql|bak)$" ) {
        return 403;
    }

    # Static assets cache
    location ~* \.(jpg|jpeg|gif|png|bmp|svg|webp|ico|woff2?|ttf|eot)$ {
        expires 30d;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    location ~* \.(css|js)$ {
        expires 1y;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    # Deny dotfiles in storage symlink
    location ~ ^/storage/\. { deny all; }

    access_log  /www/wwwlogs/restaurant-mamiviet.com.log;
    error_log  /www/wwwlogs/restaurant-mamiviet.com.error.log;
}
```

Sau khi save qua aaPanel UI → panel auto reload Nginx. Hoặc manual:
```bash
nginx -t && systemctl reload nginx
```

### 1.10 Queue worker (Supervisor)

Install + config Supervisor để chạy queue:

```bash
apt install -y supervisor

cat > /etc/supervisor/conf.d/mamiviet-queue.conf <<'EOF'
[program:mamiviet-queue-default]
process_name=%(program_name)s_%(process_num)02d
command=php /www/wwwroot/restaurant-mamiviet.com/artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www
numprocs=1
redirect_stderr=true
stdout_logfile=/www/wwwroot/restaurant-mamiviet.com/storage/logs/queue-default.log
stopwaitsecs=3600

[program:mamiviet-queue-instagram]
process_name=%(program_name)s_%(process_num)02d
command=php /www/wwwroot/restaurant-mamiviet.com/artisan queue:work --queue=instagram-scraping --sleep=5 --tries=3 --timeout=600 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www
numprocs=1
redirect_stderr=true
stdout_logfile=/www/wwwroot/restaurant-mamiviet.com/storage/logs/queue-instagram.log
stopwaitsecs=3600
EOF

supervisorctl reread
supervisorctl update
supervisorctl start mamiviet-queue-default:* mamiviet-queue-instagram:*
supervisorctl status
```

**Tách 2 worker lý do:**
- `default` queue: `RegenerateSitemap` — critical, nhanh (< 1s)
- `instagram-scraping` queue: IG scrape Apify — slow (30s-3min/call), có retry timeout 120/180/240s

Nếu gộp chung → IG scrape block sitemap regen. Tách → sitemap luôn update ngay khi admin publish post.

### 1.11 Scheduler cron

```bash
crontab -u www -e
# Thêm dòng:
* * * * * cd /www/wwwroot/restaurant-mamiviet.com && php artisan schedule:run >> /dev/null 2>&1
```

Chạy scheduler daily `sitemap:generate` (cho scheduled posts auto-publish) + weekly cleanup.

### 1.12 Passwordless sudo cho `www` reload PHP-FPM (tối ưu deploy)

Deploy script gọi `sudo systemctl reload php8.3-fpm` để flush OPcache. Cấp quyền hẹp:

```bash
cat > /etc/sudoers.d/www-reload-php <<'EOF'
www ALL=NOPASSWD: /bin/systemctl reload php8.3-fpm
EOF
chmod 440 /etc/sudoers.d/www-reload-php
```

### 1.13 First deploy smoke test

```bash
# Switch sang user www để match deploy context
su - www -s /bin/bash
cd /www/wwwroot/restaurant-mamiviet.com

# Test các URL
curl -sI https://restaurant-mamiviet.com/ | head -3           # 200
curl -sI https://restaurant-mamiviet.com/blog | head -3       # 200
curl -sI https://restaurant-mamiviet.com/sitemap.xml          # 200 application/xml
curl -sI https://restaurant-mamiviet.com/blog/feed.xml        # 200 application/rss+xml
curl -sI https://restaurant-mamiviet.com/admin/login          # 200

# Check log nếu 500
tail -f storage/logs/laravel.log
```

---

## Phần 2 — Routine deploy (mỗi lần update)

Từ máy dev: push code lên `main` branch. Sau đó trên server:

```bash
ssh root@<VPS_IP>
su - www
cd /www/wwwroot/restaurant-mamiviet.com
./deploy.sh
```

Script tự làm:
1. Check branch = main + working tree clean
2. Enable maintenance mode
3. Git pull origin main
4. `composer install --no-dev --optimize-autoloader`
5. `bun install --frozen-lockfile` + `bun run build`
6. `php artisan migrate --force`
7. Clear + cache config/route/view/event
8. Regenerate sitemap
9. Restart queue worker (picks up new code)
10. Reload PHP-FPM (flush OPcache)
11. Disable maintenance mode

**Options:**
- `./deploy.sh --skip-build` — chỉ deploy backend changes (bỏ qua frontend build nếu không đổi React code)
- `./deploy.sh --fresh` — ⚠️ DROP + re-migrate + seed (chỉ dùng cho môi trường staging/reset)

---

## Phần 3 — Post-deploy tasks

### 3.1 Google Search Console
1. Verify ownership qua meta tag — admin Filament → Settings → SEO → "Google site verification token" → dán code
2. Redeploy: `./deploy.sh --skip-build` (chỉ cần rebuild cache)
3. Submit sitemap: https://search.google.com/search-console → `https://restaurant-mamiviet.com/sitemap.xml`

### 3.2 SEO audit
- Lighthouse: https://pagespeed.web.dev/?url=https%3A%2F%2Frestaurant-mamiviet.com
- Rich Results Test: https://search.google.com/test/rich-results → test 4 URLs
- Feed Validator: https://validator.w3.org/feed/check.cgi?url=https%3A%2F%2Frestaurant-mamiviet.com%2Fblog%2Ffeed.xml

### 3.3 Admin workflow
1. Login https://restaurant-mamiviet.com/admin
2. Global Settings → SEO tab → nhập keywords + OG image cho Home, Bilder
3. Posts → Create → viết bài thực (thay 3 demo posts)
4. Trước khi publish → "Preview draft" để check
5. Publish → sitemap auto regenerate (observer)

---

## Phần 4 — Troubleshooting

### 4.1 500 Internal Server Error
```bash
tail -f /www/wwwroot/restaurant-mamiviet.com/storage/logs/laravel.log
tail -f /www/wwwlogs/restaurant-mamiviet.com.error.log
```
Phổ biến:
- Permissions: `chown -R www:www storage bootstrap/cache`
- `.env` thiếu APP_KEY: `php artisan key:generate --force`
- OPcache cache bản cũ: `sudo systemctl reload php8.3-fpm`

### 4.2 Nginx 404 cho routes non-root
- Thiếu `try_files $uri $uri/ /index.php?$query_string;` trong REWRITE
- Root trỏ sai (phải `/public`, không phải repo root)

### 4.3 Queue jobs không chạy
```bash
supervisorctl status
supervisorctl restart mamiviet-queue:*
tail -f storage/logs/queue.log
```

### 4.4 Sitemap không update khi post publish
- Queue worker không chạy → step 4.3
- Observer không register → check `app/Providers/AppServiceProvider.php::boot()` có `Post::observe(PostObserver::class)`
- Thủ công: `php artisan sitemap:generate`

### 4.5 Image upload 404
- Storage symlink thiếu: `php artisan storage:link`
- Permissions: `chown -R www:www storage/app/public`

### 4.6 SSL renew fail
aaPanel auto-renew SSL. Nếu fail:
```bash
certbot renew --dry-run
```

### 4.7 Clone fail: "destination path already exists"
aaPanel auto-tạo thư mục khi setup website. Phải backup + xoá trước:
```bash
mv /www/wwwroot/restaurant-mamiviet.com /root/backup/old-$(date +%Y%m%d)
cd /www/wwwroot && git clone https://github.com/locdev-2001/mamiviet.git restaurant-mamiviet.com
```

### 4.8 `php artisan` báo "Failed to open vendor/autoload.php"
Chưa chạy `composer install`. Thứ tự đúng:
```bash
composer install --no-dev --optimize-autoloader   # TRƯỚC
php artisan key:generate --force                   # SAU
```

### 4.9 Site 403 / directory index forbidden
Nginx `root` đang trỏ repo root thay vì `/public`:
```bash
grep -n "^ *root " /www/server/panel/vhost/nginx/restaurant-mamiviet.com.conf
# Expect: root /www/wwwroot/restaurant-mamiviet.com/public;
# Fix qua aaPanel → Websites → site → Directory tab → Website directory
# Hoặc:
sed -i 's|root /www/wwwroot/restaurant-mamiviet.com;|root /www/wwwroot/restaurant-mamiviet.com/public;|g' /www/server/panel/vhost/nginx/restaurant-mamiviet.com.conf
nginx -t && systemctl reload nginx
```

### 4.10 `/admin/login` trả 405 (GET)
Thiếu Laravel rewrite. Panel → URL rewrite → paste:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```
Note: 405 khi POST `/admin/login` là **đúng** (Filament dùng Livewire POST tới `/livewire/update`).

### 4.11 `/livewire/livewire.min.js` 404
Livewire assets chưa publish:
```bash
php artisan vendor:publish --tag=livewire:assets --force
php artisan filament:assets
```
Đã có trong `deploy.sh` từ giờ trở đi.

### 4.12 `bun install` fail "tarball extraction"
Network flaky hoặc cache corrupted:
```bash
rm -rf node_modules ~/.bun/install/cache
bun pm cache rm 2>/dev/null
bun install --frozen-lockfile
```
Fallback npm:
```bash
USE_NPM=1 ./deploy.sh
```

### 4.13 aaPanel config save fail: "Do not modify the 404 rule commented in the SSL config"
Panel validator yêu cầu giữ nguyên dòng `#error_page 404/404.html;` ngay sau `#SSL-START`. Nếu lỡ xoá → thêm lại:
```nginx
#SSL-START SSL related configuration, do NOT delete or modify the next line of commented-out 404 rules
#error_page 404/404.html;
    ssl_certificate ...
```

### 4.14 PHP warnings: "Module already loaded" (mbstring, pdo_pgsql)
Benign — aaPanel `php.ini` load duplicate extensions built-in. Fix optional:
```bash
grep -rn "mbstring\|pdo_pgsql" /www/server/php/83/etc/php.d/ 2>/dev/null
# Comment out dòng extension=mbstring hoặc extension=pdo_pgsql trong file tìm được
```

### 4.15 Instagram scrape không chạy
2 khả năng:
1. **Env keys thiếu**: kiểm tra `.env`:
   ```bash
   grep "^INSTAGRAM_" .env
   ```
   Phải có `INSTAGRAM_USERNAME` + `INSTAGRAM_API_TOKEN`. Nếu dùng `Setting::set()` thay env:
   ```bash
   php artisan tinker
   >>> \App\Models\Setting::set('instagram.username', 'mami.viet');
   >>> \App\Models\Setting::set('instagram.token', 'apify_...');
   ```

2. **Queue worker sai queue name**: `ScrapeInstagramPostsJob` dùng queue `instagram-scraping`, không phải `default`. Supervisor phải có 2 programs riêng — xem § 1.10.

---

## Phần 5 — Rollback

Nếu deploy mới gây issue:

```bash
cd /www/wwwroot/restaurant-mamiviet.com

# 1. Enable maintenance
php artisan down

# 2. Revert code to previous commit
git log --oneline -5                  # find last-good commit SHA
git reset --hard <SHA>

# 3. Re-install deps matching old commit
composer install --no-dev --optimize-autoloader
bun install --frozen-lockfile && bun run build

# 4. Rollback migration (nếu có migrate mới)
php artisan migrate:rollback --step=1

# 5. Cache rebuild
php artisan config:cache route:cache view:cache

# 6. Restart queue + reload PHP-FPM
php artisan queue:restart
sudo systemctl reload php8.3-fpm

# 7. Disable maintenance
php artisan up
```

---

## Phần 6 — Backup strategy

aaPanel GUI → **Cron** → add 2 jobs:

1. **DB backup** daily 03:00:
   - Type: Backup Database
   - Database: mamiviet
   - Keep: 7 days
   - Upload to: aaPanel cloud storage hoặc Google Drive

2. **Files backup** weekly Sunday 04:00:
   - Type: Backup Website
   - Site: restaurant-mamiviet.com (loại trừ `storage/app/public`, `public/build`, `vendor`, `node_modules`)
   - Keep: 4 weeks

Restore test: mỗi tháng 1 lần restore vào staging để verify backup hoạt động.

---

## Phần 7 — Monitoring (optional nhưng khuyến nghị)

- **Uptime**: [UptimeRobot](https://uptimerobot.com) free 50 monitors, 5-min interval
- **Analytics**: Plausible self-host / Cloudflare Web Analytics (privacy-friendly, không cần cookie banner)
- **Error tracking**: Sentry free tier cho Laravel
- **Log aggregation**: BetterStack Logs free tier (10GB/month)

---

## Checklist trước khi deploy lần đầu

- [ ] DB created + user granted
- [ ] `.env` copied + filled (DB_PASSWORD, APP_URL, INSTAGRAM_API_TOKEN)
- [ ] PHP 8.3 extensions cài đủ (gd, intl, zip, exif, bcmath, fileinfo, opcache)
- [ ] Bun hoặc Node 20+ installed
- [ ] `composer install --no-dev --optimize-autoloader` OK
- [ ] `php artisan key:generate --force` sau composer install
- [ ] `bun install && bun run build` OK (retry + npm fallback nếu fail)
- [ ] `vendor:publish --tag=livewire:assets --force` + `filament:assets`
- [ ] Nginx config updated (root → `/public`)
- [ ] Laravel rewrite ở `/www/server/panel/vhost/rewrite/<domain>.conf`
- [ ] SSL cert active
- [ ] Storage symlink created (`php artisan storage:link`)
- [ ] Migrations + GlobalSettingsSeeder run
- [ ] Filament admin user created (`make:filament-user`)
- [ ] Supervisor 2 programs: `mamiviet-queue-default` + `mamiviet-queue-instagram`
- [ ] Cron `schedule:run` active
- [ ] Permissions 775 cho storage + bootstrap/cache + public/build + public/storage
- [ ] Passwordless sudo cho `www` reload php8.3-fpm (optional)
- [ ] Smoke test 5 URLs pass (`/`, `/blog`, `/admin/login`, `/sitemap.xml`, `/blog/feed.xml`)
- [ ] `deploy.sh` executable (`chmod +x deploy.sh`)

## Checklist mỗi lần deploy

- [ ] Code pushed lên `main`
- [ ] SSH vào server
- [ ] `cd /www/wwwroot/restaurant-mamiviet.com`
- [ ] `./deploy.sh`
- [ ] Verify 200 OK: `curl -sI https://restaurant-mamiviet.com/`
- [ ] Check log `tail -20 storage/logs/laravel.log`
- [ ] Test admin login
- [ ] Announce to team (nếu có)
