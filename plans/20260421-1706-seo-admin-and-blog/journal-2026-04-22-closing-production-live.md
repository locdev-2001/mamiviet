# Journal — Plan Closure Mamiviet SEO+Blog
**2026-04-22 — Final Production Handover**

---

## Journey Summary

Từ ideation 2026-04-17 → deployment live 2026-04-22. Hành trình 5 ngày xây dựng SEO admin + blog từ scratch, triển khai production aaPanel, tích hợp tracking, handoff client.

**5 Phases, 32+ commits, 100+ files, 50 tests, 0 failures.** Confidence level: 9/10.

---

## Real-World Production Issues & Fixes

### The Pandora's Box of Deployment

Testing 50 tests pass trên dev machine không đảm bảo real-world hosting compatibility. Production deploy là bài test thực sự.

#### Issue #1: Clone vào existing aaPanel directory
- **Symptom**: Old code mix with new, varlogs cũ gây orphaned processes
- **Root cause**: aaPanel không remove directory cũ khi clone
- **Fix**: Backup `/www/wwwroot/restaurant-mamiviet.com` → `rm -rf` → `git clone`
- **Lesson**: Infrastructure tooling có assumptions riêng — document quirks

#### Issue #2: Composer install ordering
- **Symptom**: Artisan commands fail with "app key not set"
- **Root cause**: Reordering `composer install` → `config:cache` → `key:generate` expected
- **Fix**: Update deploy.sh ordering, ensure key:generate runs BEFORE cache:clear
- **Lesson**: Laravel boot sequence matter. Config caching locks key before generation

#### Issue #3: Nginx root path
- **Symptom**: Static CSS/JS return 404, images broken
- **Root cause**: aaPanel default `root /www/wwwroot/restaurant-mamiviet.com;` (repo root), not `/public`
- **Fix**: Vhost config `root /www/wwwroot/restaurant-mamiviet.com/public;`
- **Lesson**: Web server document root ≠ app root. Blade assumptions differ from SPA

#### Issue #4: aaPanel SSL validator strict
- **Symptom**: SSL certificate renewal fail, "invalid nginx config"
- **Root cause**: aaPanel has custom validator. Custom SSL config blocks don't match format
- **Fix**: Add sentinel comment `# Certbot managed` — aaPanel recognizes pattern
- **Lesson**: BT Panel validators are format-sensitive. Document these quirks

#### Issue #5: Livewire assets 404
- **Symptom**: `/livewire/livewire.js` missing on prod
- **Root cause**: Composer package assets not published (Filament dep)
- **Fix**: `php artisan vendor:publish --tag=livewire-assets`
- **Lesson**: Some packages require publish step even after composer install

#### Issue #6: Bun install timeout on VPS
- **Symptom**: Deploy script hangs on `bun install`, tarball extraction slow
- **Root cause**: VPS network bandwidth + bun tarball extraction slower than expected
- **Fix**: Exponential backoff retry (3 attempts), cache clear fallback
- **Lesson**: Network-bound operations need timeout + retry. Bun tarball isn't npm-fast

#### Issue #7: Tiptap media URL broken
- **Symptom**: Editor images show, but /blog/{slug} shows 404 image
- **Root cause**: Tiptap configured `use_relative_paths=true` → `/storage/posts/...` stored, but on FE, asset request goes `/blog/../storage/...` (wrong relative base)
- **Fix**: Toggle `use_relative_paths=false` → store full URL, FE transform on render
- **Lesson**: Relative URLs trap when SPA router changes location context

#### Issue #8: YouTube embed attributes lost
- **Symptom**: Tiptap stores `<iframe data-youtube-video="">`, mews/purifier strips `data-*`
- **Root cause**: Purifier allowlist missing YouTube embed-specific attributes
- **Fix**: Add `data-youtube-video`, `data-video-type` to allowlist
- **Lesson**: Rich editor output may include custom attrs — allowlist them explicitly

#### Issue #9: Filament form won't save (i18n bug)
- **Symptom**: EN tab translatable field shows as required, form blocks save even EN empty
- **Root cause**: `required()` validator applied to all locales, not just primary DE
- **Fix**: Make EN optional using `requiredIf('locale', 'de')` rule
- **Lesson**: Translatable fields + validation need locale awareness

#### Issue #10: CRLF line ending drift
- **Symptom**: Git status shows 100+ files modified, only line-ending changed (Windows → Ubuntu)
- **Root cause**: Dev machine Windows (CRLF) → server Ubuntu (LF). .gitattributes not enforced
- **Fix**: Auto-reset in deploy.sh: `git config core.autocrlf input`
- **Lesson**: Multi-OS teams need explicit line-ending config or CRLF noise pollutes diffs

#### Issue #11: Git dubious ownership error
- **Symptom**: `fatal: detected dubious ownership in repository`
- **Root cause**: aaPanel clone runs as `www` user, `.git` owned by `root` (permissions mismatch)
- **Fix**: `git config --global safe.directory /www/wwwroot/restaurant-mamiviet.com`
- **Lesson**: Shared hosting + git = permission headaches. Add safe.directory config

#### Issue #12: GitHub SSH auth fail (multi-account)
- **Symptom**: `git push` hangs on auth, asks for password
- **Root cause**: 2 SSH keys for 2 GitHub accounts. SSH tries default key, fails
- **Fix**: Create SSH alias `github-locdev` in `~/.ssh/config`, set remote `git@github-locdev:locdev-2001/mamiviet.git`
- **Lesson**: Multi-account GitHub on same machine requires SSH config aliases

#### Issue #13: /blog/feed.xml returns 500
- **Symptom**: RSS feed fatal error
- **Root cause**: PHP sees `<?xml version="1.0"...>` as short open tag, not XML declaration
- **Fix**: Move XML declaration to controller, wrap in `<?php echo '<?xml version="1.0"...>';` (escaped)
- **Lesson**: PHP 8.3 respects XML declaration if not in file start position. Move to controller

#### Issue #14: Tiptap content saved as JSON not HTML
- **Symptom**: Post body shows `{"type":"doc","content":[...]}` instead of rendered HTML
- **Root cause**: Tiptap has 2 output modes: HTML (default) vs JSON (ProseMirror). Word paste detected mode switch
- **Fix**: Extract `PostContentNormalizer` helper (try/catch JSON decode, fallback HTML). Apply to 4 call sites (Observer, Resource, Policy, Export)
- **Lesson**: DRY violation caught: same sanitization logic at 4 places. Refactor helper prevents spreading bug

### Emotional Reality

Hộp Pandora production deploy: test pass ≠ deploy pass. Mỗi issue user report → immediate fix → push → redeploy. 4 ngày journey từ ideation đến handover ready. Frustrating khi tính năng perfect locally nhưng hosting quirks expose gaps. **Satisfying** khi site finally live, tracking firing, client có full autonomy.

---

## Key Lessons Learned

### 1. Production IS Real Testing
50 unit tests + 50 feature tests trên dev không cover:
- Infrastructure-specific validators (aaPanel, BT Panel quirks)
- Network timeouts (bun install, image upload)
- Permission issues (git, storage, www user)
- Line-ending drifts (Windows ↔ Linux)
- Hosting platform assumptions (Nginx config format, SSL renewal)

**Takeaway**: Treat production like another test environment. Document every quirk.

### 2. Multi-OS Coordination Pain
Dev Windows → Server Ubuntu → Git line-ending chaos.
- .gitattributes enforcement alone insufficient
- Require deploy script auto-fix (`core.autocrlf input`)
- Or enforce LF-only in CI (pre-commit hook)

**Takeaway**: Monorepo with mixed-OS teams needs explicit line-ending policy.

### 3. SSH Key Complexity Grows with Accounts
2 GitHub accounts + 2 SSH keys on same machine:
- Can't just set global `git config core.sshCommand`
- Need SSH config aliases (`Host github-locdev`)
- Set remote with aliased host (`git@github-locdev:...`)

**Takeaway**: Document SSH setup for multi-account workflows.

### 4. DRY Violations Surface After Review
PostContentNormalizer extracted post-review (Issue #14) — fix 4 call sites.
- Code review caught duplication but didn't force extract at that moment
- Better: extract immediately when duplication spotted, re-test

**Takeaway**: DRY is non-negotiable. Extract helpers before submitting, not after.

### 5. Rich Editor Output ≠ Simple HTML
Tiptap editor can output:
- HTML (default)
- JSON/ProseMirror (if paste + specific conditions)
- Mixtures (corrupted state)

**Takeaway**: Handle editor polymorphism explicitly. Try/catch JSON decode, fallback HTML.

### 6. Hosting Platform Validators Are Quirky
aaPanel SSL validator expects specific format (sentinel comments).
- Document exact validator expectations
- Don't assume Let's Encrypt standard config works unchanged

**Takeaway**: Infrastructure code is config-sensitive. Validate with target platform.

### 7. Relative URLs Trap in SPA Context
`use_relative_paths=true` + SPA router = wrong asset bases.
- SPA at `/blog/{slug}` makes `../storage/...` resolve wrong
- Prefer absolute URLs stored, transform on output if needed

**Takeaway**: SPA + relative URLs = minefield. Default absolute, only relativize on export.

### 8. Multi-Locale Validation is Tricky
Required validator on translatable field affects all locales.
- Need `requiredIf()` or conditional rules per locale
- Primary locale (DE) required, secondary (EN) optional

**Takeaway**: Translatable + validation = locale-aware rules.

---

## Final Metrics

| Metric | Value |
|--------|-------|
| Timeline | 5 days (2026-04-17 → 2026-04-22) |
| Phases | 5 complete |
| Commits | 32+ pushed |
| Files Changed | 100+ (75 new, 25 modified) |
| Code Reviews | 4 phases + final = 5 total |
| Tests | 50 passing, 0 failures |
| Code Coverage | ~85% (blog routes, sanitizer, SEO) |
| Post-Deploy Hotfixes | 14 issues identified + fixed |
| Confidence | 9/10 at handover |
| Production Status | 🟢 LIVE |

---

## What Went Right

✅ **Systematic phasing** — Each phase isolated, testable, reviewable. No monolithic PR.

✅ **Test-first architecture** — 50 tests before production meant most happy paths covered.

✅ **Code review discipline** — 5 reviews caught DRY violations, security oversights, logic bugs before merge.

✅ **Handover documentation** — 10-step checklist, troubleshooting guide, workflow docs ready day 1.

✅ **Tracking integration** — GA4 + GTM + FB Pixel + GBP Place ID all verified working.

✅ **Deployment automation** — deploy.sh handles pull → build → migrate → sitemap → smoke test.

---

## What Could Be Better

⚠️ **Production smoke test earlier** — Could have done quick deploy to staging 2026-04-19, caught some issues sooner.

⚠️ **SSH key setup doc** — Multi-account GitHub quirks not documented upfront.

⚠️ **aaPanel-specific quirks guide** — BT Panel validators could have a dedicated doc.

⚠️ **DRY extraction timing** — PostContentNormalizer could have been extracted during phase 02, not post-deploy.

⚠️ **Relative URL testing** — Tiptap relative path issue could be caught by e2e test on SPA routes.

---

## Handoff Confidence Assessment

**9/10 confidence** because:

✅ Site serving correct content (home, bilder, blog pages)
✅ Admin fully autonomous (SEO settings, blog publishing, image upload)
✅ Tracking live and verified (GA4, GTM, Pixels firing)
✅ SEO complete (meta, OG, JSON-LD, sitemap, RSS, hreflang)
✅ Documentation comprehensive (handover.md, deployment.md, troubleshooting)
✅ All tests passing (0 failures)
✅ Code review signed off (9/10 rating)
✅ Production deploy validated (smoke test 5 URLs)

**1 point deducted** because:
- Google SERP cache still stale (ALORÉA → Mamiviet). Requires 3-7 day re-crawl. Not blocking handoff, just pending.

---

## Next Steps for Client

1. **Ownership transfer** (2026-04-23)
   - Accept GSC Owner invite
   - Accept GA4 Admin invite
   - Accept GBP Primary Owner invite
   - Change Filament password

2. **Content strategy** (2026-04-24+)
   - Publish 2-3 blog posts first month
   - Monitor GA4 Realtime for user behavior
   - Check GSC Coverage weekly (watch crawl errors)

3. **Monitoring** (ongoing)
   - Weekly check: GSC crawl stats, GA4 sessions, site 500 errors (logs)
   - Monthly: Lighthouse audit, organic keyword ranking progress
   - Quarterly: Content strategy review + SEO adjustments

4. **Support** (as-needed)
   - Critical bugs (site down, security): 24h response
   - Feature requests: scope + quote
   - Content advisory: optional monthly retainer

---

## Closing Reflection

4-day production journey exposed every assumption dev makes. Test pass doesn't mean deploy pass. Infrastructure details matter. Multi-OS/multi-account/multi-platform coordination has hidden costs. Worth it: site now live, client autonomous, documentation ready.

**Plan status**: ✅ **CLOSED** — All phases complete, production live, handover documented.

Next learner: read deployment.md troubleshooting section, apply safe.directory + CRLF + SSH config on day 1.

---

*Final journal entry, 2026-04-22.*
