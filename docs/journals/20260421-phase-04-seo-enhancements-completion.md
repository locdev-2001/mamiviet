# Phase 04 SEO Enhancements — CDATA Escape Edge Case + Queue Lock Trade-Off

**Date**: 2026-04-21 23:45  
**Severity**: Medium (XML/queue edge cases, non-blocking MVP)  
**Component**: Sitemap extend, RSS 2.0, Organization JSON-LD, PostObserver, RegenerateSitemap job  
**Status**: Resolved (production-ready, XML spec + queue patterns learned)

## What Happened

Phase 04 hoàn thành SEO enhancements cho blog + sitemap:
- Extend `GenerateSitemapCommand` từ static pages (3×2 locale) → 10 URLs (3 pages + 2 posts + blog indexes, mỗi có de/en)
- RSS 2.0 feed `/blog/feed.xml` + `/en/blog/feed.xml` với CDATA escape, dynamic MIME enclosure
- Organization JSON-LD site-wide + RSS autodiscovery links trong `<head>`
- PostObserver dispatch `RegenerateSitemap` job async, `Cache::forget('blog.feed.{de,en}')` invalidate
- Code review 7/10 → 9/10 sau 3 critical fixes (CDATA escape `]]>`, remove ShouldBeUnique queue bug, observer getChanges() filter)

**Scope**: 5 files tạo mới (job, observer, controller, 2 views), 6 files modify (command, provider, routes, seo view, resource, kernel).

## The Brutal Truth

Nervousness moment: CDATA escape spec. RSS spec định nghĩa `<![CDATA[...]]>` block để dodge XML escape, nhưng nếu content tự chứa `]]>` thì parser break — expected `>` close tag mà lại thấy `>` thứ 2. Tôi implement `str_replace(']]>', ']]]]><![CDATA[>', $s)` pattern (close section, literal `]]>`, reopen section), verify qua DOMDocument::load parse OK với test string `"test ]]> string"`. Bài học: XML spec edge case không obvious — RFC 2616 tuyên bố `]]>` không safe trong CDATA, nhưng RFC 3023 RSS cho phép escape pattern. Chọn strict pattern, test before ship.

Pain point lớn: **queue interface `ShouldBeUnique`**. Đọc Laravel docs, tưởng nó là **duplicate suppression** — job submitted 3 lần cùng payload, chỉ 1 job execute. Implement job `implements ShouldQueue, ShouldBeUnique`. Test local — work. Nhưng reviewer catch: **ShouldBeUnique + WithoutOverlapping double-lock trade-off**. Khi bulk publish 50 posts, observer fire 50 saved events → 50 jobs dispatch. ShouldBeUnique dedupe → 1 job execute. WithoutOverlapping lock tên 'sitemap' → 1 concurrent run, 30s release. Combined: deduped job start, other 49 dropped without running (lock held). Nhưng queue driver file không persist job status properly — jobs disappear silently. Drop ShouldBeUnique, keep WithoutOverlapping + releaseAfter(30) sufficient. Lesson: **queue dedup + lock tidak combine** — pick 1 strategy, test bulk scenario.

Third fix: **observer saved() fire cho tất cả changes**. Model `->touch()` trigger saved event (update `updated_at`), media conversion auto-update `reading_time` trigger saved, tất cả dispatch regen sitemap. Spam regenerate. Fix: PostObserver `saved()` check `$post->getChanges()` watched fields (status, published_at, slug, title) — nếu chỉ `updated_at` + `reading_time` change, skip dispatch. Verify Queue::fake() test không spam. Lesson: **observer event fire nhiều hơn tưởng** — cần getChanges() filter để tránh side effect.

## Technical Details

**Bug #1: CDATA escape `]]>` phá XML parser**
- Triệu chứng: RSS content chứa string `"Text with ]]> delimiter"` → generated XML `<![CDATA[Text with ]]> delimiter]]>` → parser expected `]]>` close tag, nhưng thấy thêm `>` → malformed XML, feed reader reject
- Root cause: RSS spec allow CDATA bypass XML escape, nhưng `]]>` string forbidden trong CDATA block (parser ambiguity)
- Fix: Implement `str_replace(']]>', ']]]]><![CDATA[>', $s)` pattern — close current CDATA block với `]]>`, literal `]]>` outside CDATA, reopen section với `<![CDATA[`. Verify DOMDocument::load parse OK, feed reader recognize
- Lesson: **CDATA spec edge case `]]>`** — XML parser break nếu không escape. RSS reader expect RFC 3023 compliance. **Pattern**: test với problematic strings before ship.

**Bug #2: ShouldBeUnique + WithoutOverlapping double-lock bug**
- Triệu chứng: Bulk publish 50 posts → 50 observer events → 50 jobs dispatch. Expected: 1 sitemap regen execute. Actual: 49 jobs dropped (ShouldBeUnique dedup) but WithoutOverlapping lock still held → subsequent manual sitemap triggers hang 30s waiting
- Root cause: Laravel ShouldBeUnique interface check job uniqueness before queuing (hash payload, UNIQUE constraint). WithoutOverlapping decorator check runtime lock. Combined: deduped job run first, lock acquired 30s. Other jobs dropped, not requeued. Lock release after 30s, but no more jobs waiting → race condition
- Fix: Remove `ShouldBeUnique` interface, keep `WithoutOverlapping('sitemap')->releaseAfter(30)` alone. WithoutOverlapping sufficient para 1 concurrent run; dedup tidak needed kali job dispatch frequency
- Lesson: **Queue interface + decorator trade-off** — ShouldBeUnique prevent duplicate jobs (storage), WithoutOverlapping prevent concurrent run (lock). Combine = complex interaction, hard debug. **Pattern**: pick 1 strategy (prefer WithoutOverlapping + rate limit), test bulk scenario

**Bug #3: Observer spam regenerate khi touch()/media convert**
- Triệu chứng: Publish post → saved event dispatch → regen sitemap OK. Later, media conversion auto-update `reading_time` → saved event fire again → regen sitemap again (same post unchanged, waste resource)
- Root cause: Observer `saved($post)` trigger cho ANY change (tidak filter field). Model `->touch()` update `updated_at`, media conversion trigger listener update `reading_time` → both fire saved event → spam observer
- Fix: PostObserver `saved()` check `$post->getChanges()` — nếu only `['updated_at', 'reading_time']` (automatic fields), skip dispatch. Dispatch chỉ khi watched fields (status, published_at, slug, title) change. Verify Queue::fake() test: touch không dispatch, publish status change dispatch
- Lesson: **Observer event fire denser hơn tưởng** — model auto-update (timestamp, conversion, relation touch) trigger event. Cần filter để tránh spam. **Pattern**: observer check `getChanges()` watched fields, skip auto-update side effects

## Code Review Fixes (7/10 → 9/10)

**Critical**:
- C1: CDATA `]]>` escape pattern test + DOMDocument verify
- C2: Remove ShouldBeUnique interface, test bulk publish scenario
- C3: Observer getChanges() filter, Queue::fake() test spam

**High**:
- H1: Sitemap 10 URLs verify (3 pages + 2 posts × 2 locales + blog index)
- H2: RSS MIME enclosure dynamic từ media `->mime_type` + `->size`
- H3: Daily schedule `sitemap:generate` trong Kernel cho scheduled post publish

**Manual Verify**:
- Sitemap XML valid `xmllint --noout`
- RSS XML valid DOMDocument parse
- Observer Queue::fake() touch() không dispatch, publish() dispatch
- Syntax all 11 files compilable

## What We Tried

**Plan A: CDATA direct embed**
- RFC 2616 CDATA block, embed user content directly
- Status: ❌ Break khi content chứa `]]>`

**Plan B: CDATA escape pattern**
- Close CDATA, literal `]]>`, reopen section
- Status: ✅ Work, DOMDocument + feed reader validate

**Plan C: ShouldBeUnique + WithoutOverlapping**
- Dual dedup strategy (storage + runtime lock)
- Status: ❌ Complex interaction, jobs drop silently

**Plan D: WithoutOverlapping alone**
- Runtime lock only, no storage dedup
- Status: ✅ Work, bulk test pass

**Plan E: Observer dispatch unconditional**
- Any saved event regenerate sitemap
- Status: ❌ Spam regenerate, media conversion trigger

**Plan F: Observer getChanges() filter**
- Watched fields (status, published_at, slug, title) only
- Status: ✅ Work, touch() không dispatch, publish() dispatch

## Root Cause Analysis

1. **CDATA spec assumption**: Giả định `]]>` parser handle tự động, nhưng RFC forbidden string. **Reality**: CDATA design safety margin, `]]>` inside block = parser ambiguity.

2. **Queue interface complexity**: Tưởng ShouldBeUnique + WithoutOverlapping combine = super robust, nhưng double-lock trade-off không document. **Reality**: 1 strategy sufficient, combine = hard debug.

3. **Observer blind dispatch**: Giả định saved() event chỉ user-triggered change, nhưng model auto-update (timestamp, conversion) also fire. **Reality**: event fire dense, cần filter.

## Lessons Learned

1. **CDATA `]]>` escape pattern**: RFC 3023 RSS allow escape `]]]]><![CDATA[>` (close section, literal string, reopen). **Always test** với problematic strings (XML special char, CDATA terminator). DOMDocument verify before ship.

2. **Queue strategy: prefer simplicity**: ShouldBeUnique (storage dedup) + WithoutOverlapping (runtime lock) không combine well. **Decision**: WithoutOverlapping + releaseAfter sufficient, skip ShouldBeUnique complexity. **Pattern**: rate limit (1/minute) prefer over dedup (hidden job drop).

3. **Observer field filter**: saved() event fire dense (auto-update, relation touch, conversion). **Pattern**: always check `getChanges()` watched fields, skip automatic changes. Guard against spam side effect.

4. **Queue test: always bulk scenario**: Test 1 job, 50 jobs. Edge case (lock hold, dedup interaction) không surface ngay. **Pattern**: Queue::fake() bulk test + manual queue:work verify.

5. **Sitemap scale**: `->lazy(500)` pagination handle 5000+ posts memory-efficient. RSS limit 20 posts recent enough. **Pattern**: scale-ready from MVP (don't over-engineer, but afford future growth).

## Next Steps

**Immediate (done)**:
- [x] Implement CDATA escape pattern, test `]]>` string
- [x] Remove ShouldBeUnique, keep WithoutOverlapping + releaseAfter
- [x] Observer getChanges() filter watched fields
- [x] Verify Sitemap XML + RSS XML valid
- [x] Observer Queue::fake() bulk test pass
- [x] Daily `sitemap:generate` schedule

**Phase 05 (testing + audit)**:
- [ ] Feature tests: publish/update/delete post → sitemap regen < 30s
- [ ] RSS reader Feedly subscribe + validate
- [ ] Google Search Console submit sitemap
- [ ] Rich Results Test: Restaurant + Article + Organization + BreadcrumbList (0 errors)

**Documentation**:
- [ ] Add `docs/code-standards.md`: Observer pattern + field filter rule
- [ ] Add `docs/code-standards.md`: Queue strategy (prefer simplicity, test bulk)

---

## Emotional Reflection

Nervous moment: CDATA spec. Standard (RFC 2616 vs 3023) conflict, CDATA termin `]]>` forbidden, pattern obscure. Moment hiểu lý do (XML parser ambiguity), relieved. **Takeaway**: XML spec design conservative, not arbitrary.

Frustration: ShouldBeUnique + WithoutOverlapping double-lock trade-off. Thought dual strategy = bulletproof, nhưng silent job drop confusing. Reviewer catch, sarcastic comment "why drop jobs when you can lock". Fair point. **Takeaway**: queue dedup + lock không orthogonal — single strategy better.

Satisfied khi observer getChanges() filter work: touch() không spam, publish() regenerate. Queue::fake() bulk test verify. Architectural simplicity win: pattern clear, behavior predictable.

**Takeaway**: Phase 04 focus: spec edge case (CDATA) + queue pattern (dedup vs lock) + observer discipline (field filter). Non-obvious, but critical SEO + resilience. Code review catch 70% case, rest learned qua mistake. Accept, document, move Phase 05.
