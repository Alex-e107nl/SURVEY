# Survey Plugin Upgrade: PHP 8.3+ / e107 2.4

**Version:** 0.4.10 → 1.0.0
**Date:** 2026-11-14
**Status:** ✅ COMPLETE

---

## Phase 0: Safety ✅

- [x] **0.1** Backup original plugin → `C:\Users\alex\Goose\survey_backup_0.4.10`

## Phase 1: Fatal Error Fixes ✅

- [x] **1.1** Replace `new convert` → `e107::getDate()` (survey.php, view.php, stats.php, print.php)
- [x] **1.2** Remove `$ns = new e107table;` from np() functions (survey.php, view.php, print.php)
- [x] **1.3** Remove `require_once(e_HANDLER."np_class.php");` from print.php
- [x] **1.4** THEME.'style.css' kept (THEME constant still defined in 2.4)
- [x] **1.5** Language loading: `e107::plugLan()` / `e107::lan()` → `e107::includeLan(e_PLUGIN.'survey/languages/'.e_LANGUAGE.'/...')`
  - Added `e107::coreLan('np')` where NP_1/NP_2 used (survey.php, view.php, print.php)
- [x] **1.6** All `require_once("../../class2.php")` → `require_once(__DIR__.'/../../class2.php')`
- [x] **1.7** Created `plugin.xml` (modern) + `survey_sql.php` (InnoDB, utf8mb4). Updated `plugin.php` (InnoDB, version 1.0.0)
- [x] **1.8** Admin guard: `if(!getperms("P")){e107::redirect('admin');exit;}` + `$e_sub_cat='survey'`
- [x] **1.9** Forum check: `e107::isInstalled('forum')` before loading forum_class.php
- [x] **1.10** Restructured: shared functions (myform, show_form_question, show_form_field) moved to survey.inc.php

## Phase 2: PHP 8.3 Fatal Fixes ✅

- [x] **2.1** `count($_POST['results'][$fn])` → `empty($v) || !is_array($v)` (survey.php checkbox check)
- [x] **2.2** All `unserialize()` calls guarded with `@` prefix + `is_array()` check
- [x] **2.3** All `extract($row)` replaced with explicit variable assignments
- [x] **2.4** SURVEY_PRINT/SURVEY_LIST/SURVEY_EDIT double-define bug → single `define()` with ternary
- [x] **2.5** All `$_POST[...]` reads use `?? ''` null coalescing
- [x] **2.6** Undefined `$size`/`$maxlength` in case 8 → `explode` with defaults
- [x] **2.7** `$_res[$selected_rec]` undefined access → `isset()` check
- [x] **2.8** `$fvalue[$f]=$v` (undefined `$f`) → removed (dead code)
- [x] **2.9** `$row` undefined after db_Fetch → `is_array($row)` guard
- [x] **2.10** `count($fields)` after unserialize → `is_array` guard first

## Phase 3: Security Hardening ✅

- [x] **3.1** SQL injection: All `$_POST['existing']` / `$arg[0]` → `intval()` before SQL
- [x] **3.2** CRLF header injection: `preg_replace('/[^a-zA-Z0-9_\-\s]/', '_', $survey_name)` + quoted filename
- [x] **3.3** Regex injection: `preg_quote($stext, '/')` in all survey_search() functions
- [x] **3.4** eval() → `survey_safe_math()` with whitelist regex `'/^[\d+\-*/().\s]+$/'`
- [x] **3.5** CSRF: `e-token` hidden field in all forms + `$tokenOk` check on all POST handlers
- [x] **3.6** Output escaping: `e_html_escape()` on user data in HTML output
- [x] **3.7** SSRF in isImage(): scheme check (http/https only), DNS resolution, private IP block, 5s timeout
- [x] **3.8** Forum anonname: `e_html_escape($_POST['anonname'] ?? '')`
- [x] **3.9** DB results: `$tp->toDB()` on serialized data before INSERT/UPDATE

## Phase 4: Modernization ✅

- [x] **4.1** `e107::getMessage()` pattern for admin messages (admin_config.php)
- [x] **4.2** Legacy `form` class kept (still works in 2.4, e_form migration optional)
- [x] **4.3** np() pagination kept (local function, no e107table dependency)
- [x] **4.4** Image paths: `e_PLUGIN."survey/images/..."` for portability
- [x] **4.5** Version bump: 0.4.10 → 1.0.0
- [x] **4.6** Date picker: kept Dynarch Calendar (survey_cal.js) for backward compat

## Phase 5: Verification ✅

- [x] **5.1** `php -l` syntax check: All 13 files pass PHP 8.3.31
- [x] **5.2** No `new convert` references remain
- [x] **5.3** No `new e107table` references remain
- [x] **5.4** No `np_class.php` references remain
- [x] **5.5** No `plugLan` / `e107::lan` references remain
- [x] **5.6** No `extract()` calls remain
- [x] **5.7** No `require_once("../../` relative paths remain
- [x] **5.8** All `unserialize()` calls have `@` prefix + `is_array()` guard
- [x] **5.9** `e107::getDate()` confirmed in e107_class.php line 2159

---

## Files Modified (13)

| File | Lines | Changes |
|------|-------|---------|
| `plugin.xml` | 13 | NEW - Modern plugin descriptor |
| `survey_sql.php` | 39 | NEW - InnoDB/utf8mb4 table DDL |
| `plugin.php` | 106 | Version 1.0.0, InnoDB, extract guard |
| `admin_config.php` | 398 | CSRF, getMessage, intval, extract→explicit, toDB/toHTML |
| `survey.php` | 516 | getDate, SSRF fix, CSRF, unserialize guards, forum check |
| `survey.inc.php` | 174 | Shared functions, unserialize guards, $size/$maxlength fix |
| `view.php` | 634 | getDate, define bug fix, intval, CSRF, safe_math, preg_quote |
| `stats.php` | 159 | getDate, intval, unserialize guards, is_array checks |
| `print.php` | 433 | np_class removed, getDate, intval, CSRF, safe_math, preg_quote |
| `excel.php` | 239 | CRLF fix, intval, unserialize guards, safe_math, preg_quote |
| `word.php` | 236 | CRLF fix, intval, unserialize guards, safe_math, preg_quote |
| `e_header.php` | 6 | Minor cleanup |
| `help.php` | 53 | includeLan pattern |

## Remaining Work (Manual Testing)

- [ ] Fresh install on e107 2.4 + PHP 8.3
- [ ] Create survey in admin, add fields of all types
- [ ] Front-end: fill and submit survey
- [ ] Admin: edit, reorder, copy, delete survey
- [ ] View results, search, list mode, edit/delete records
- [ ] Stats page
- [ ] Print view
- [ ] Excel + Word export
- [ ] Mail notification (survey_mailto + emailto fields)
- [ ] Forum posting (if forum plugin installed)
- [ ] Upgrade path from 0.4.10 (existing data intact)
- [ ] Language files (Dutch, French, Italian, Norwegian, Spanish)
