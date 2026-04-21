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
# What it does (safe order):
#   1. Confirm working tree clean + on main branch
#   2. Pull latest from origin/main
#   3. composer install --no-dev (production deps only)
#   4. bun install + bun run build (unless --skip-build)
#   5. php artisan migrate --force
#   6. Clear + cache config/route/view/event
#   7. Regenerate sitemap
#   8. Restart queue worker (so it picks up new code)
#   9. Reload PHP-FPM (flush OPcache)
#
# Pre-requisites on server:
#   - Git remote `origin` set, SSH key or PAT configured
#   - PHP 8.3+, composer, bun installed (bun alternative: npm with `--use-npm` flag manually)
#   - MySQL DB created + user granted
#   - .env exists (copy from .env.production.example)
#   - storage/ + bootstrap/cache/ writable by www user
#   - Supervisor or systemd running `php artisan queue:work`
#   - Cron: `* * * * * php artisan schedule:run`

set -Eeuo pipefail

# ---------- Configuration ----------
BRANCH="main"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"   # override via env if different
USE_NPM="${USE_NPM:-0}"                            # set 1 to use npm instead of bun

SKIP_BUILD=0
FRESH_DB=0
for arg in "$@"; do
    case "$arg" in
        --skip-build) SKIP_BUILD=1 ;;
        --fresh)      FRESH_DB=1 ;;
        -h|--help)
            sed -n '2,30p' "$0"
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

on_error() {
    fail "Deploy aborted. Check above output. Site may be in a partial state — run 'php artisan up' if maintenance mode stuck."
}
trap on_error ERR

# ---------- Pre-flight ----------
[[ -f artisan ]] || fail "artisan not found. Run this from Laravel project root."
[[ -f .env ]]    || fail ".env missing. Copy .env.production.example → .env and fill in values."

current_branch=$(git rev-parse --abbrev-ref HEAD)
if [[ "$current_branch" != "$BRANCH" ]]; then
    fail "Not on branch '$BRANCH' (current: '$current_branch'). Refusing to deploy."
fi

if ! git diff-index --quiet HEAD --; then
    fail "Working tree has uncommitted changes. Commit or stash before deploying."
fi

log "Enabling maintenance mode"
php artisan down --retry=15 --render="errors::503" 2>/dev/null || warn "Maintenance mode skipped (view not found)"

# ---------- Pull ----------
log "Fetching origin..."
git fetch origin "$BRANCH" --tags

local_sha=$(git rev-parse HEAD)
remote_sha=$(git rev-parse "origin/$BRANCH")
if [[ "$local_sha" == "$remote_sha" ]]; then
    warn "Already at latest ($local_sha). Continuing with rebuild anyway."
else
    log "Pulling $local_sha..$remote_sha"
    git reset --hard "origin/$BRANCH"
fi

# ---------- Composer ----------
log "Installing composer dependencies (production)..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# ---------- Frontend build ----------
if [[ "$SKIP_BUILD" -eq 0 ]]; then
    if [[ "$USE_NPM" -eq 1 ]]; then
        log "Installing npm dependencies..."
        npm ci
        log "Building frontend (npm run build)..."
        npm run build
    else
        log "Installing bun dependencies..."
        bun install --frozen-lockfile
        log "Building frontend (bun run build)..."
        bun run build
    fi
    ok "Frontend built → public/build/"
else
    warn "Skipping frontend build (--skip-build)"
fi

# ---------- Storage link ----------
if [[ ! -L public/storage ]]; then
    log "Creating storage symlink"
    php artisan storage:link
fi

# ---------- Database ----------
if [[ "$FRESH_DB" -eq 1 ]]; then
    warn "FRESH DB requested — this DROPS ALL TABLES"
    read -r -p "Type 'yes' to confirm: " confirm
    [[ "$confirm" == "yes" ]] || fail "Cancelled"
    php artisan migrate:fresh --force
    php artisan db:seed --class=GlobalSettingsSeeder --force
else
    log "Running migrations..."
    php artisan migrate --force
fi

# ---------- Cache ----------
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
php artisan filament:cache-components

# ---------- Sitemap ----------
log "Regenerating sitemap.xml"
php artisan sitemap:generate

# ---------- Queue ----------
log "Restarting queue workers"
php artisan queue:restart

# ---------- OPcache / PHP-FPM ----------
if command -v systemctl >/dev/null 2>&1; then
    if systemctl is-active --quiet "$PHP_FPM_SERVICE"; then
        log "Reloading $PHP_FPM_SERVICE (flush OPcache)"
        sudo systemctl reload "$PHP_FPM_SERVICE" || warn "Failed to reload $PHP_FPM_SERVICE (need passwordless sudo). Run manually: sudo systemctl reload $PHP_FPM_SERVICE"
    else
        warn "$PHP_FPM_SERVICE not active — skipping reload"
    fi
else
    warn "systemctl not available — skip PHP-FPM reload"
fi

# ---------- Disable maintenance ----------
log "Disabling maintenance mode"
php artisan up

# ---------- Summary ----------
ok "Deploy complete → $(git rev-parse --short HEAD) on $BRANCH"
echo
echo "  Sitemap:  $(grep -c '<url>' public/sitemap.xml 2>/dev/null || echo 0) URLs"
echo "  Commit:   $(git log -1 --pretty=format:'%h %s')"
echo "  Time:     $(date '+%Y-%m-%d %H:%M:%S %Z')"
