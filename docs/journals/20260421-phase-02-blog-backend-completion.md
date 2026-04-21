# Phase 02 Blog Backend — Completion + 5 Runtime Bugs Fixed

**Date**: 2026-04-21 18:45  
**Severity**: High (5 critical UX breaks found after "verification")  
**Component**: Post model + Filament resource + tiptap editor + HTML sanitizer  
**Status**: Resolved (all bugs fixed before merge)

## What Happened

Phase 02 xây dựng blog backend hoàn chỉnh cho Mamiviet:
- Post model translatable (de/en) với soft deletes
- Filament PostResource 3 tabs (Content/SEO/Publishing)
- Tiptap rich editor (`awcodes/filament-tiptap-editor:^3.0`)
- HTML sanitizer (`stevebauman/purify` + custom TiptapPurifyDefinition)
- Draft preview qua signed URL
- Reading time auto-computed dùng UTF-8 aware word count

**Scope**: 14 files tạo mới, 4 files modify. Tất cả code test qua `php artisan tinker` lẫn manual Filament form interaction trước khi tưởng là "done". Nhưng user phát hiện 5 bugs runtime khi test thực tế form flow — không ai bắt được lúc manual verify.

## The Brutal Truth

Điều cay đắng: **"tinker test pass" không bằng "UI form interaction pass"**. Tôi verify model logic (slug auto-gen, reading_time calc, content sanitize) bằng tinker với fixtures, nhưng lại miss:
- Form component API misunderstanding (nhầm giữa plugins khác nhau)
- Admin panel plugin lifecycle (SpatieLaravelTranslatablePlugin chưa register = form crash)
- Media upload flow qua tiptap action mounting (relative path logic sai)
- HTML attribute whitelist không preserve data attrs mà tiptap cần lúc parse

Cảm giác lúc fix: **chỉ là sơ suất nhưng tạo ra 5 lỗi liên tác**, mỗi cái phải dùng DevTools Network tab hoặc DevTools Console mới thấy rõ. Nếu tôi chỉ ngồi ở terminal, sẽ không biết gì đã sai.

## Technical Details

**Bug #1: SpatieLaravelTranslatablePlugin chưa register**
- Triệu chứng: Filament form crash khi load EditPost page (JS error "localeData undefined")
- Root cause: Thêm trait `Translatable` vào Resource nhưng quên register plugin ở AdminPanelProvider
- Fix: `AdminPanelProvider->register()` thêm `.plugin(SpatieLaravelTranslatablePlugin::class)`
- Lesson: **Plugin pattern trong Filament**: register once ở provider, trait auto-enable form behavior

**Bug #2: TextInput::translatable() không tồn tại**
- Triệu chứng: "Call to undefined method `translatable()`" khi form render
- Root cause: Nhầm lẫn với package `aymanalareqi/filament-translatable-fields` (tôi đọc search result Web nhầm). Official plugin không cung cấp `->translatable()` per-field. Pattern đúng:
  - Resource trait + Pages traits (ListRecords/CreateRecord/EditRecord `Concerns\Translatable`)
  - LocaleSwitcher action header
  - Form fields build **một lần duy nhất**, plugin switch locale qua context
- Fix: Xoá toàn bộ `->translatable()` calls, dùng `static::form()` không locale-aware
- Lesson: **Filament Spatie plugin không magic per-field** — nó switch form context (de/en), fields lấy giá trị từ context locale tự động

**Bug #3: Image upload 404 khi edit lần 2**
- Triệu chứng: Upload ảnh thành công lần 1, save bài. Edit bài, ảnh show (thumbnail), nhưng khi upload ảnh khác → 404 trên network (GET `/storage/posts/content/...` fail)
- Root cause: Tiptap editor package MediaAction `use_relative_paths=true` setting (mặc định config) có bug: `mountUsing` event strip `/storage/` prefix từ đường dẫn (để save relative), nhưng action handler lúc update **không re-add prefix** → DB lưu path sai như `posts/content/...` thay vì `/storage/posts/content/...` → khi render src sai
- Fix: Set `use_relative_paths=false` ở `config/filament-tiptap-editor.php` để package tự manage full path
- Lesson: **Package config default không lúc nào là "safe"** — phải test upload + edit flow. Relative path chỉ tốt nếu package xử lý toàn bộ normalization; tiptap có bug → dùng absolute path an toàn hơn

**Bug #4: YouTube/Vimeo oembed không lưu được**
- Triệu chứng: Embed YouTube iframe via tiptap oembed tool → HTML lưu vào DB đúng (có `<div data-youtube-video>` wrapper). Nhưng sau khi save, edit bài lại → iframe biến mất (wrapper vẫn ở nhưng iframe rỗng)
- Root cause: Purifier allowlist không preserve `data-youtube-video`, `data-aspect-ratio`, `data-width`, `data-height` attributes → lúc sanitize, wrapper `<div>` mất tất cả data attrs. Lúc tiptap parse HTML để render lại, nó dùng data attrs để generate iframe → missing attrs = skip render
- Fix: 
  1. Thêm vào Purify `HTML.Allowed`: `div[data-youtube-video|data-vimeo-video|data-aspect-ratio|data-width|data-height]`
  2. Tạo `TiptapPurifyDefinition` thêm attributes vào allowlist (Definition pattern của `stevebauman/purify`)
  3. Verify: `Purify::config('blog')->clean($html)` preserve data attrs
- Lesson: **Tiptap YouTube/Vimeo node gắn chặt vào wrapper data attrs** — nếu sanitizer strip, parsing logic breakdown. Phải test **round-trip**: embed → sanitize → parse → render

**Bug #5: Video drag-resize không hoạt động**
- Triệu chứng: User mong muốn resize iframe qua drag ở UI tiptap
- Root cause: Tiptap `oembed` extension **không support resize** (design limitation, không phải bug code). Chỉ support `NodeViewProps.selected` highlight
- Action: **Deferred to Phase 03** — CSS wrapper responsive container `.tiptap-oembed { aspect-ratio: 16/9; width: 100%; height: auto }` + container div thay vì iframe trực tiếp
- Lesson: **Not all feature requests are bugs** — tiptap oembed extension scope hẹp, phải accept limitation hoặc swap extension

**Additional Code Review Findings** (3 CRITICAL bắt trước merge):
1. Slug UNIQUE INDEX với `deleted_at` → MySQL NULL-distinctness bug (UNIQUE tính NULL như unique value, dùng 2 soft-deleted post with same slug → conflict). Fix: Validator closure check `whereNull('deleted_at')` before error
2. `str_word_count()` không UTF-8 → umlaut tiếng Đức (ä ö ü ß) đếm sai 2× → reading_time inflate. Fix: `preg_split('/\s+/u', ...)` with PCRE `u` flag
3. `status=published` + `published_at=NULL` → scope ẩn bài, nhưng form helper text "Leave empty to publish immediately" không match code. Fix: Saving event auto set `published_at=now()` khi publish + null

## What We Tried

**Plan A: Tinker verification + fixture tests**
- Model saving event: test slug auto-gen, reading_time calc, content sanitize qua tinker
- Status: ✅ Pass (logic đúng), nhưng **không test form flow**, nên miss plugin register + UI component API issues

**Plan B: Manual Filament UI test**
- Create post, fill form, upload ảnh, embed video
- Status: ✅ Partial (create work), nhưng **không test edit flow** (image 404 bug chỉ match edit lần 2), không test oembed round-trip (sanitize loss)

**Plan C (tôi đã fix khi user report)**
- Enable DevTools Network tab, watch request flow
- Inspect HTML source sau save, compare giữa lần 1 vs lần 2
- Trace Purify output vs Tiptap parse input → identify attribute loss
- Status: ✅ Work (tất cả bug fixed), nhưng **reactive fix, không proactive test**

## Root Cause Analysis

1. **Plugin registration oversight**: Thêm trait mà quên provider setup. Lesson: **Filament plugin = trait + provider**, không thể một mà không cái kia
2. **Package API confusion**: Web search trả lại package khác → nhầm API. Lesson: **Context7 documentation > Web search** — phải dùng official docs, không search "filament translatable" vì có 20+ packages
3. **Incomplete test scenario**: Tinker test solo model, không test model + form + file upload interaction. Lesson: **Integration test PHẢI gồm full flow** — model save → form render → edit → save lại
4. **Assumption về package quality**: Giả định `use_relative_paths=true` safe vì package nổi tiếng. Lesson: **Config default ≠ tested default** — phải đọc package source khi set `true` lần đầu
5. **Sanitizer allowlist incomplete**: Allowlist common HTML nhưng quên data attrs pattern. Lesson: **Custom package integration + rich editor = extra allowlist testing**, không thể generic sanitizer config cho tiptap

## Lessons Learned

1. **Tinker ≠ UI**: Tinker test model logic, nhưng form component lifecycle (plugin init, file upload mounting, locale switching) không thể test ở CLI. **PHẢI có integration test hoặc manual UI test qua browser**.

2. **Official plugin ≠ all-in-one**: Filament Spatie Translatable plugin không magic per-field. **Pattern**: Resource trait + Pages traits + LocaleSwitcher action. Phải đọc plugin README carefully.

3. **Data attributes + sanitizer**: Rich editor dùng data attrs để lưu metadata (YouTube ID, aspect ratio). Sanitizer strip attributes → editor parse fail. **Test: embed → save → edit → check output**. Allowlist phải include data pattern.

4. **Relative path configs**: Package config `use_relative_paths=true` cool nhưng có edge case (tiptap MediaAction mounting). **Safer**: use absolute path nếu không 100% test complete flow. Context: Mamiviet chỉ run 1 domain, APP_URL không đổi → absolute path an toàn.

5. **Context7 > Web search**: Nhầm `aymanalareqi/filament-translatable-fields` (unofficial) vs `filament/spatie-laravel-translatable-plugin` (official) vì search result chứa cả 2. **Rule**: Always resolve library ID qua context7 trước coding.

## Next Steps

**Immediate (done)**:
- [x] Register SpatieLaravelTranslatablePlugin ở AdminPanelProvider
- [x] Remove `->translatable()` per-field, dùng official pattern
- [x] Set `use_relative_paths=false` ở tiptap config
- [x] Thêm data-youtube-video, data-vimeo-video attributes vào Purify allowlist
- [x] Test embed → save → edit → check oembed presence

**Phase 03 (deferred)**:
- CSS responsive video wrapper (aspect-ratio container)
- Frontend rendering `/blog`, `/blog/{slug}` + DOMPurify re-sanitize
- Extract `App\Support\ImageUrl` helper (lại phát hiện lần này, deferred từ Phase 01)

**Code debt**:
- [ ] `App\Support\SeoBuilder` — DRY seo_title/seo_description/seo_keywords pattern (used by GlobalSettings + Post)
- [ ] Integration test suite: `PostTest` cover create → edit → delete with all media types + sanitizer

**Documentation**:
- [ ] Add to `docs/code-standards.md`: Filament plugin registration pattern
- [ ] Add to `docs/code-standards.md`: Sanitizer + rich editor allowlist testing checklist

---

## Emotional Reflection

Frustration đỉnh điểm lúc fix 5 bugs liên tiếp sau tưởng đã "done" — cảm giác lãng phí công sức verify tinker khi missed các vấn đề UI. **Nhưng** user phản hồi rất professional: test cụ thể (screenshot DevTools, describe reproduce step), feedback không toxic. Mỗi bug report tôi khoái — rõ ràng, actionable, giúp fix nhanh.

Satisfying moment: Fix tiptap `use_relative_paths` bug bằng cách đọc package source code → trace flow `mountUsing` → identify config issue. Cảm giác "ah, đó là lý do" khi thấy code pattern.

**Takeaway**: Tinker test OK cho model logic, nhưng Filament + file upload + rich editor = **phải manual test UI + Network tab inspection**. Sẽ thêm integration test suite Phase 03.
