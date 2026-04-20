# Inertia.js v2 + Laravel 10 + React 18 SSR Setup Research

**Date:** 2026-04-20  
**Project:** Mamiviet Restaurant CMS  
**Context:** Migrate SPA (React Router) → Inertia v2 with SSR + locale routing  

---

## 1. Install Commands & Version Constraints

### Composer (PHP)
```bash
composer require inertiajs/inertia-laravel:^2.0
```
- **Requirement:** Laravel 10+ (your current version ✓), PHP 8.1+
- **v2.0 stable** released; handles break changes from v1

### NPM (JavaScript)
```bash
npm install @inertiajs/react @inertiajs/vue3 @vitejs/plugin-react react react-dom
npm install --save-dev @vitejs/plugin-react
```
- Use `@inertiajs/react:^2.0` for React 18 compatibility
- **Note:** v3.0 requires React 19; stay on v2 for React 18 projects
- `@vitejs/plugin-react` auto-enables JSX + refresh

### Verify Installation
```bash
composer show inertiajs/inertia-laravel
npm ls @inertiajs/react
```

---

## 2. Vite Config Update (vite.config.ts)

Replace your current Vite config:

```typescript
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/js/app.tsx'],        // Client-side entry
      ssr: 'resources/js/ssr.tsx',            // SSR entry (NEW)
      refresh: true,
    }),
    react({
      jsxRuntime: 'automatic',                 // automatic JSX transform
    }),
  ],
  resolve: {
    alias: {
      '@': '/src',
    },
  },
})
```

**Breaking Changes from Your Current Config:**
- Old: manifest mode + `public/build/` output auto-handled  
- New: Vite plugin handles output → Laravel reads automatically via `@vite` directive
- SSR entry point must exist at `resources/js/ssr.tsx`

### Package.json Build Script
```json
{
  "scripts": {
    "dev": "vite",
    "build": "vite build && vite build --ssr",
    "lint": "eslint . --ext .tsx,.ts"
  }
}
```

---

## 3. SSR Sidecar Setup

### File Structure
Create these files:

**resources/js/app.tsx** (Client Entry)
```typescript
import React from 'react'
import { createRoot } from 'react-dom/client'
import { createInertiaApp } from '@inertiajs/react'

createInertiaApp({
  resolve: (name) => resolvePageComponent(
    `./pages/${name}.tsx`,
    import.meta.glob('./pages/**/*.tsx')
  ),
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
```

**resources/js/ssr.tsx** (Server Entry)
```typescript
import React from 'react'
import ReactDOMServer from 'react-dom/server'
import { createInertiaApp } from '@inertiajs/react'

export default function render(page: any) {
  return createInertiaApp({
    page,
    render: ReactDOMServer.renderToString,
    resolve: (name) => resolvePageComponent(
      `./pages/${name}.tsx`,
      import.meta.glob('./pages/**/*.tsx')
    ),
    setup({ App, props }) {
      return <App {...props} />
    },
  })
}
```

### Laravel Bootstrap Template (resources/views/app.blade.php)
```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>Mamiviet</title>
    @vite('resources/js/app.tsx')
    @inertiaHeadTags
</head>
<body>
    @inertia
</body>
</html>
```

**How it works:**
- `@inertia` → renders SSR'd HTML or mounts client-side React
- `@vite` → auto-loads JS + CSS from Vite manifest
- `@inertiaHeadTags` → injects <meta>, <title> from page props

### Start SSR Development
```bash
npm run dev                      # Vite watches client + SSR entries
php artisan serve               # Laravel dev server (or Laragon)
```

No separate SSR process needed in dev mode (Vite handles it).

### Production Build & SSR Server
```bash
npm run build                   # Builds both client + SSR bundles
php artisan inertia:start-ssr   # Starts Node.js process (port 13714 default)
php artisan inertia:check-ssr   # Verify server is running
```

Output structure:
```
bootstrap/ssr/
├── ssr.mjs                  # Compiled SSR server
public/build/
├── app.js, app.css          # Client bundles
├── manifest.json            # Asset manifest
```

---

## 4. Locale Routing Pattern (Middleware-Based)

**Routes (routes/web.php):**
```php
Route::group(['middleware' => ['setLocale']], function () {
    Route::get('/', IndexController::class)->name('index');
    Route::get('/bilder', BilderController::class)->name('bilder');
    
    // EN prefix group
    Route::group(['prefix' => 'en'], function () {
        Route::get('/', IndexController::class)->name('index.en');
        Route::get('/bilder', BilderController::class)->name('bilder.en');
    });
});
```

**Middleware (app/Http/Middleware/SetLocale.php):**
```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->route('locale') ?? $request->getDefaultLocale();
        
        if (!in_array($locale, ['de', 'en'])) {
            $locale = 'de';  // Default
        }
        
        app()->setLocale($locale);
        
        return $next($request);
    }
}
```

Register in `app/Http/Kernel.php`:
```php
protected $routeMiddleware = [
    'setLocale' => \App\Http\Middleware\SetLocale::class,
];
```

**Why middleware over Route::prefix:**
- Cleaner when same controllers serve multiple locales
- Locale available in `app()->getLocale()` for all downstream code
- Works with Inertia shared data

---

## 5. Inertia Shared Data (HandleInertiaRequests)

**Create middleware (app/Http/Middleware/HandleInertiaRequests.php):**
```php
<?php
namespace App\Http\Middleware;

use Inertia\Inertia;
use Illuminate\Http\Request;

class HandleInertiaRequests
{
    public function handle(Request $request, $next)
    {
        Inertia::share([
            'locale' => app()->getLocale(),
            'translations' => $this->loadTranslations(),
            'flash' => fn() => [
                'success' => session('success'),
                'error' => session('error'),
            ],
            'auth' => [
                'user' => auth()->user(),
            ],
        ]);

        return $next($request);
    }

    private function loadTranslations()
    {
        $locale = app()->getLocale();
        
        return [
            'common' => require resource_path("lang/{$locale}/common.php"),
            'pages' => require resource_path("lang/{$locale}/pages.php"),
        ];
    }
}
```

Register in `app/Http/Kernel.php`:
```php
protected $middleware = [
    // ...
    \App\Http\Middleware\HandleInertiaRequests::class,
];
```

**Key Points:**
- Shared data sent on **every** page load (SSR + client)
- `flash()` callback returns fresh session data each request
- Load only needed translation keys (avoid dumping entire lang files)

---

## 6. Page Components & TypeScript

**resources/js/pages/Index.tsx:**
```typescript
import React, { useMemo } from 'react'
import { Head } from '@inertiajs/react'
import Layout from '@/components/Layout'

export default function Index(props) {
  // Props automatically typed by Inertia in full setup
  const { translations, locale } = props

  return (
    <Layout>
      <Head title="Home" />
      <div>
        <h1>{translations.pages.home.title}</h1>
      </div>
    </Layout>
  )
}
```

**Page Component Props Shape:**
```typescript
// Inertia injects:
interface PageProps {
  locale: string
  translations: {
    common: Record<string, string>
    pages: Record<string, Record<string, string>>
  }
  flash: {
    success?: string
    error?: string
  }
  auth: {
    user: User | null
  }
}
```

---

## 7. Asset Handling & Hydration

**@vite Directive:**
```blade
<!-- In app.blade.php -->
@vite('resources/js/app.tsx')
```

Vite plugin **automatically**:
- Inlines SSR'd HTML from Node.js
- Injects `<script>` + `<style>` tags client-side
- Hydrates React with server-rendered DOM

**No manual asset tags needed** — Vite + Laravel plugin handles it.

---

## 8. Migration Gotchas (React Router → Inertia)

### Before (React Router)
```typescript
import { useNavigate, useParams } from 'react-router-dom'

export default function Post({ id }) {
  const navigate = useNavigate()
  const { postId } = useParams()

  return (
    <button onClick={() => navigate(`/posts/${id}`)}>
      View Post
    </button>
  )
}
```

### After (Inertia)
```typescript
import { Link, router } from '@inertiajs/react'

export default function Post({ postId }) {
  // No useParams — props come from server via Inertia
  
  return (
    <>
      {/* Declarative: */}
      <Link href={`/posts/${postId}`}>View Post</Link>
      
      {/* Programmatic: */}
      <button onClick={() => router.visit(`/posts/${postId}`)}>
        View Post
      </button>
    </>
  )
}
```

**Key Diffs:**
1. **No Route definitions in React** — all routes in Laravel
2. **No useParams** — data passed as props
3. **router.visit()** = React Router's navigate
4. **<Link>** prefetches by default (faster navigation)

### Replace History Entry
```typescript
router.visit(url, { replace: true })  // Don't add browser history entry
```

---

## 9. Hydration Mismatch Prevention (Critical for SSR)

**Pattern: Client-Only Components**

Problem: Instagram feed fetched client-side → different HTML server vs client.

Solution: Defer rendering until client-side:

```typescript
import { useEffect, useState } from 'react'

export default function InstagramFeed() {
  const [isMounted, setIsMounted] = useState(false)

  useEffect(() => {
    setIsMounted(true)
  }, [])

  if (!isMounted) {
    return <div className="skeleton">Loading...</div>  // Placeholder
  }

  // Fetch + render Instagram feed HERE
  return <Feed />
}
```

**Why it works:**
- Server renders placeholder (fast, no JS needed)
- Client hydrates same placeholder first
- After hydration, `useEffect` runs → sets `isMounted = true`
- Component re-renders with real data
- No mismatch because initial render matches

**Alternative: Suppress hydration warning (not recommended)**
```typescript
<div suppressHydrationWarning>
  {typeof window !== 'undefined' ? realData : placeholder}
</div>
```

---

## 10. Production SSR with Supervisor

**supervisord config** (`/etc/supervisor/conf.d/inertia-ssr.conf`):

```ini
[program:inertia-ssr]
process_name=%(program_name)s
command=php /var/www/mamiviet/artisan inertia:start-ssr
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/inertia-ssr.log
stopwaitsecs=3600
```

**Deployment Steps:**
```bash
# 1. Build bundles
npm run build

# 2. Deploy to production (git pull, etc)

# 3. Stop old SSR process (supervisor auto-restarts)
php artisan inertia:stop-ssr

# 4. Verify SSR is running
php artisan inertia:check-ssr    # Returns HTTP 200 if healthy

# 5. Monitor logs
tail -f /var/log/supervisor/inertia-ssr.log
```

**Troubleshooting:**
- SSR listens on `localhost:13714` (default, configurable via `.env`)
- Laravel must be able to reach it for server-side rendering
- If building on different machine, transfer `public/build/` + `bootstrap/ssr/` only

---

## 11. Breaking Changes Summary (v1 → v2)

| Item | v1 | v2 |
|------|----|----|
| **PHP Requirement** | 7.4+ | 8.1+ |
| **useRemember** | `remember()` | `useRemember()` |
| **Partial Reloads** | Sync | **Async** (await router.reload) |
| **SSR Workflow** | Separate Node process during dev | Auto via Vite in dev |
| **BuildDir** | `public/hot` manifest | Vite auto-manifest |

**Action:** If upgrading existing v1 project:
1. Update package versions first
2. Audit `remember()` → `useRemember()` calls
3. Test partial reloads (now Promise-based)
4. Rebuild Vite config from scratch

---

## Adoption Risk Assessment

### Maturity
- **v2.0 stable** (2+ years in production)
- Large ecosystem (Breeze, Jetstream use Inertia v2)
- Community: active GitHub + Laracasts tutorials

### Breaking Changes
- v1 → v2 has **breaking changes** (listed above)
- v2 → v3 drops React 18 support (stay on v2 for your project)

### SSR Production Risk
- **Lower risk:** Works at Fly.io, Vercel, typical VPS setups
- **Known issue:** Port conflicts if multiple SSR processes
- **Mitigation:** Supervisor + health checks (already covered)

### Team Skill Risk
- React 18 developers: **low** (just add Inertia props + Link)
- Laravel 10 developers: **very low** (mostly config)
- SSR debugging: **medium** (hydration mismatches need useEffect pattern)

---

## Implementation Roadmap (Recommended Order)

1. **Create plan** with planner agent → phases
2. **Phase 1:** Install Inertia packages + scaffold files
3. **Phase 2:** Vite config update + build test
4. **Phase 3:** Root template (app.blade.php) + middleware
5. **Phase 4:** Migrate first page (Index) to Inertia
6. **Phase 5:** Locale routing + translations
7. **Phase 6:** Build SSR setup + test locally
8. **Phase 7:** Production deployment + Supervisor

---

## Unresolved Questions

1. **Instagram feed caching:** Should SSR fetch posts server-side (slow) or client-side (hydration mismatch risk)? Recommend client-side with useEffect pattern.
2. **Existing localStorage state:** Old React Router app uses localStorage. Inertia? Recommend migrating to session/flash messages or client-side state hooks.
3. **Admin panel (Filament):** Still separate or integrate into Inertia? Recommend separate (Filament has own routing).

---

## Sources

- [Inertia.js v2 Upgrade Guide](https://inertiajs.com/docs/v2/getting-started/upgrade-guide)
- [Inertia.js Server-Side Setup](https://inertiajs.com/server-side-setup)
- [Laravel 10 Vite Documentation](https://laravel.com/docs/10.x/vite)
- [Laravel Inertia React Localization Guide](https://rybniko.medium.com/laravel-inertia-react-localization-908f4df68d14)
- [Inertia.js Manual Visits & Routing](https://inertiajs.com/docs/v2/the-basics/manual-visits)
- [React Hydration Mismatch Patterns](https://www.propelauth.com/post/understanding-hydration-errors)
- [Inertia SSR Production Setup (Fly.io)](https://fly.io/docs/laravel/advanced-guides/using-inertia-ssr/)
