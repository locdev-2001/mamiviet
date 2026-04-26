#!/usr/bin/env bash
#
# Mamiviet — production deploy script (pull-based, run on server)
#
# Usage:
#   cd /www/wwwroot/restaurant-mamiviet.com
#   ./deploy.sh                 # standard deploy
#   ./deploy.sh --skip-build    # skip frontend build (backend-only change)
#   ./deploy.sh --fresh         # drop + re-migrate + seed (DANGEROUS, first deploy only)
#
# Steps (safe order):
#   1. Preflight: branch clean, .env + APP_KEY present, PHP/bun available
#   2. Maintenance mode ON
#   3. git pull origin main
#   4. composer install --no-dev --optimize-autoloader
#   5. filament:upgrade (refresh metadata)
#   6. bun/npm install + build (with retry on failure)
#   7. Publish Livewire + Filament assets
#   8. Ensure storage symlink
#   9. migrate --force
#   10. Clear + rebuild caches (config/route/view/event/filament)
#   11. Regenerate sitemap
#   12. queue:restart (workers reload code)
#   13. Reload PHP-FPM (flush OPcache)
#   14. Maintenance mode OFF
#   15. Smoke test key URLs
#
# Pre-requisites on server (first time setup):
#   - Git remote `origin` configured (HTTPS + PAT, or SSH key)
#   - PHP 8.3+, composer, bun (or Node+npm with USE_NPM=1)
#   - MySQL DB + user created
#   - .env present with APP_KEY filled
#   - storage/ + bootstrap/cache/ writable by www user
#   - Supervisor running queue workers (default + instagram-scraping)
#   - Cron: * * * * * php artisan schedule:run
#   - Passwordless sudo for www user to reload php-fpm (optional)

set -Eeuo pipefail

# ---------- Configuration ----------
BRANCH="${DEPLOY_BRANCH:-main}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"
USE_NPM="${USE_NPM:-0}"
SITE_URL="${SITE_URL:-https://restaurant-mamiviet.com}"
SKIP_SMOKE_TEST="${SKIP_SMOKE_TEST:-0}"

SKIP_BUILD=0
FRESH_DB=0
for arg in "$@"; do
    case "$arg" in
        --skip-build)       SKIP_BUILD=1 ;;
        --fresh)            FRESH_DB=1 ;;
        --skip-smoke-test)  SKIP_SMOKE_TEST=1 ;;
        -h|--help)
            sed -n '2,40p' "$0"
            exit 0
            ;;
        *) echo "Unknown option: $arg" >&2; exit 1 ;;
    esac
done

# ---------- Helpers ----------
log()  { printf '\033[1;34m[deploy]\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m[ok]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[warn]\033[0m %s\n' "$*"; }
fail() { printf '\033[1;31m[fail]\033[0m %s\n' "$*" >&2; exit 1; }

cleanup_on_error() {
    warn "Deploy aborted. Attempting to disable maintenance mode..."
    php artisan up 2>/dev/null || true
    fail "Check output above. Site may be partially updated."
}
trap cleanup_on_error ERR

# ---------- Preflight ----------
log "Preflight checks..."

[[ -f artisan ]] || fail "artisan not found. Run this from Laravel project root."
[[ -f .env ]]    || fail ".env missing. Copy .env.production.example → .env and fill in values."

if ! grep -qE '^APP_KEY=base64:.+' .env; then
    fail "APP_KEY missing or invalid in .env. Run: php artisan key:generate --force"
fi

command -v php >/dev/null || fail "php not installed"
command -v composer >/dev/null || fail "composer not installed"

php_version=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
if [[ $(echo "$php_version < 8.3" | bc -l 2>/dev/null || echo 0) -eq 1 ]]; then
    warn "PHP $php_version detected. Laravel 10 needs 8.1+, recommended 8.3."
fi

if [[ "$SKIP_BUILD" -eq 0 ]]; then
    if [[ "$USE_NPM" -eq 1 ]]; then
        command -v npm >/dev/null || fail "npm not installed. Install Node 20+."
    else
        command -v bun >/dev/null || fail "bun not installed. Install: curl -fsSL https://bun.sh/install | bash. Or set USE_NPM=1."
    fi
fi

# Auto-register safe.directory (git 2.35+ requires for root-owned dirs)
repo_path=$(pwd)
if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    warn "Registering safe.directory exception for $repo_path"
    git config --global --add safe.directory "$repo_path" || fail "Failed to register safe.directory"
fi

# Force LF line-endings locally (prevents CRLF drift from Windows dev → Linux server)
git config --local core.autocrlf false 2>/dev/null || true
git config --local core.eol lf 2>/dev/null || true

current_branch=$(git rev-parse --abbrev-ref HEAD)
[[ "$current_branch" == "$BRANCH" ]] || fail "Not on branch '$BRANCH' (current: '$current_branch'). Refusing to deploy."

# Auto-reset Laravel skeleton .gitignore files (CRLF drift from old clones is harmless noise)
if ! git diff-index --quiet HEAD -- storage/ bootstrap/cache/; then
    warn "Auto-resetting storage/ + bootstrap/cache/ .gitignore drift"
    git checkout -- storage/ bootstrap/cache/ 2>/dev/null || true
fi

if ! git diff-index --quiet HEAD --; then
    warn "Working tree has uncommitted changes:"
    git status --short | head -20
    fail "Commit, stash, or reset before deploying. Common fix: git checkout -- ."
fi

ok "Preflight passed"

# ---------- Maintenance mode ----------
log "Enabling maintenance mode"
php artisan down --retry=15 2>/dev/null || warn "Maintenance mode skipped (artisan down failed)"

# ---------- Pull ----------
log "Fetching origin/$BRANCH..."
git fetch origin "$BRANCH" --tags --prune

local_sha=$(git rev-parse HEAD)
remote_sha=$(git rev-parse "origin/$BRANCH")
if [[ "$local_sha" == "$remote_sha" ]]; then
    warn "Already at latest ($local_sha). Continuing with rebuild anyway."
else
    log "Pulling ${local_sha:0:7}..${remote_sha:0:7}"
    git reset --hard "origin/$BRANCH"
fi

# ---------- Composer ----------
log "Installing composer dependencies (production)..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

log "Running filament:upgrade (refresh component metadata)"
php artisan filament:upgrade --ansi || warn "filament:upgrade failed (non-critical)"

# ---------- Frontend build ----------
install_with_retry() {
    local max_attempts=2
    for attempt in $(seq 1 $max_attempts); do
        if [[ "$USE_NPM" -eq 1 ]]; then
            if npm ci --no-audit --no-fund; then
                return 0
            fi
        else
            if bun install --frozen-lockfile; then
                return 0
            fi
        fi

        warn "Install attempt $attempt failed. Clearing cache + retry..."
        rm -rf node_modules
        if [[ "$USE_NPM" -eq 1 ]]; then
            npm cache clean --force 2>/dev/null || true
        else
            bun pm cache rm 2>/dev/null || rm -rf ~/.bun/install/cache
        fi
    done
    return 1
}

if [[ "$SKIP_BUILD" -eq 0 ]]; then
    log "Installing frontend dependencies..."
    install_with_retry || fail "Frontend install failed after retries. Check network + disk space. Fallback: USE_NPM=1 ./deploy.sh"

    log "Building frontend..."
    if [[ "$USE_NPM" -eq 1 ]]; then
        npm run build
    else
        bun run build
    fi

    [[ -f public/build/manifest.json ]] || fail "Build output missing public/build/manifest.json"
    ok "Frontend built → public/build/ ($(ls public/build/assets/ 2>/dev/null | wc -l) assets)"
else
    warn "Skipping frontend build (--skip-build)"
fi

# ---------- Publish vendor assets (idempotent) ----------
log "Publishing Livewire + Filament assets"
php artisan vendor:publish --tag=livewire:assets --force --ansi 2>/dev/null || warn "livewire:assets publish skipped"
php artisan filament:assets --ansi 2>/dev/null || warn "filament:assets publish skipped"

# ---------- Storage link ----------
if [[ ! -L public/storage ]]; then
    log "Creating storage symlink"
    php artisan storage:link
fi

# ---------- Database ----------
if [[ "$FRESH_DB" -eq 1 ]]; then
    warn "⚠️  FRESH DB requested — this DROPS ALL TABLES"
    read -r -p "Type 'yes' to confirm destructive reset: " confirm
    [[ "$confirm" == "yes" ]] || fail "Cancelled"
    php artisan migrate:fresh --force --seed
    php artisan db:seed --class=GlobalSettingsSeeder --force
else
    log "Running migrations..."
    php artisan migrate --force
    log "Ensuring homepage section defaults exist..."
    php artisan db:seed --class=PagesSeeder --force
fi

# ---------- Cache rebuild ----------
log "Clearing old caches"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

log "Building production caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:cache-components 2>/dev/null || warn "filament:cache-components skipped"

# ---------- Sitemap ----------
log "Regenerating sitemap.xml"
php artisan sitemap:generate

# ---------- Queue ----------
log "Broadcasting queue:restart (workers pick up new code on next job)"
php artisan queue:restart

# ---------- OPcache / PHP-FPM ----------
if command -v systemctl >/dev/null 2>&1; then
    if systemctl is-active --quiet "$PHP_FPM_SERVICE"; then
        log "Reloading $PHP_FPM_SERVICE (flush OPcache)"
        if sudo -n systemctl reload "$PHP_FPM_SERVICE" 2>/dev/null; then
            ok "PHP-FPM reloaded"
        else
            warn "Failed to reload $PHP_FPM_SERVICE (need passwordless sudo)."
            warn "Run manually: sudo systemctl reload $PHP_FPM_SERVICE"
        fi
    else
        warn "$PHP_FPM_SERVICE not active — skipping reload"
    fi
else
    warn "systemctl not available — skip PHP-FPM reload"
fi

# ---------- Disable maintenance ----------
log "Disabling maintenance mode"
php artisan up

# ---------- Smoke test ----------
if [[ "$SKIP_SMOKE_TEST" -eq 0 ]] && command -v curl >/dev/null; then
    log "Smoke testing $SITE_URL..."
    smoke_failed=0
    for path in "/" "/blog" "/admin/login" "/sitemap.xml" "/blog/feed.xml"; do
        code=$(curl -sIL -o /dev/null -w "%{http_code}" --max-time 10 "$SITE_URL$path" || echo "000")
        if [[ "$code" =~ ^(200|301|302)$ ]]; then
            printf '  \033[1;32m✓\033[0m %3s  %s\n' "$code" "$path"
        else
            printf '  \033[1;31m✗\033[0m %3s  %s\n' "$code" "$path"
            smoke_failed=1
        fi
    done
    [[ "$smoke_failed" -eq 0 ]] || warn "Some smoke tests failed. Check storage/logs/laravel.log"
fi

# ---------- Summary ----------
ok "Deploy complete → $(git rev-parse --short HEAD) on $BRANCH"
echo
echo "  Sitemap:  $(grep -c '<url>' public/sitemap.xml 2>/dev/null || echo 0) URLs"
echo "  Commit:   $(git log -1 --pretty=format:'%h %s')"
echo "  Time:     $(date '+%Y-%m-%d %H:%M:%S %Z')"
