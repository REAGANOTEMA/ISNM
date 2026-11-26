# ISNM Complete System Audit Report

**Project:** Iganga School of Nursing and Midwifery website and management system
**Location:** `C:\xampp\htdocs\ISNM`
**Date:** 2026-11-26
**Method:** Static source audit (668 PHP, 47 JS, 67 CSS, 3 SQL, 3 HTML files) plus local runtime probes (PHP CLI, MySQL TCP/port/credential checks). No source files were modified during the audit.

---

## 1. Executive summary

| Area | Verdict | Notes |
|---|---|---|
| Database connectivity | **BROKEN (local)** | `igangaschool_system` and `igangaschool_website` do not exist on the running local MySQL; `.env.local` forces port 3307 but MySQL listens on 3306; configured users get `1045 Access denied`. |
| Technology stack | Working legacy PHP | Plain PHP 8.x + MySQLi + Bootstrap/jQuery dashboards, session auth, two logical databases. No framework; no build step; no composer autoload in use. |
| Public website | **PARTIAL** | 18 audited root pages pass `php -l`; most render; several forms report success after failed writes; CMS engine is orphaned; two broken CSS links. |
| Authentication / roles | **PARTIAL / INSECURE** | Multiple parallel auth stacks, inconsistent role stores, forged-session bypass in student panel, no login rate limiting in some paths, GET state changes. |
| Dashboards | **LARGE BUT UNEVEN** | 138 top-level dashboard pages + 6 endpoint files; most query real tables; several contain runtime DDL, conflicting table/status names, or placeholder buttons. |
| Security | **CRITICAL GAPS** | SQL identifier injection in `includes/form_router.php`, CSRF gaps in CMS/news/payment callbacks, IDOR in messaging/student endpoints, hardcoded credentials. |
| Reporting / exports | **PARTIAL** | CSV/PDF/print endpoints exist; formula injection and unescaped filename issues in CSV export; some transcripts generated before result verification. |
| Performance | **MIXED** | Many parameterized, bounded queries; some unbounded selects, duplicate stats queries, 3 service workers, duplicated CSS/JS libraries. |
| Testability | **BLOCKED** | No usable local database, so end-to-end writes could not be exercised until setup is repaired. |

**Blocker that must be fixed first:** local database provisioning and environment configuration.

---

## 2. Project inventory

### 2.1 File counts and structure

- PHP: 668 files; JS: 47; CSS: 67; SQL: 3; HTML: 3; JSON: 3; XML: 2; Markdown: 5; env variants: 3.
- Key directories: `admin_panel/`, `owner_panel/`, `teacher_panel/`, `student_panel/`, `dashboards/` (144 PHP files), `cms/`, `includes/`, `config/`, `database/`, `sql/`, `models/`, `controllers/`, `views/`, `api/`, `ajax/`, `handlers/`, `workflow/`, `quality-assurance/`, `payment_gateway/`, `shared/`, `assets/`, `uploads/` and seven upload folders.
- Public pages: `index.php`, `about.php`, `history.php`, `programs.php`, `organogram.php`, `organizational-structure.php` (301 redirect), `news.php`, `application.php`, `application-success.php`, `contact.php` + 6 department contact pages, `donation.php`, `volunteer.php`, `staff-login.php`, `student-login.php`, forgot/reset password pages.
- Portals: `dashboard.php`, `staff-portal.php`, `student-portal.php`, `systems_home.php`, `student_financial_portal.php`, `student_profile.php`, `student-directory.php`, legacy panel `index.php` files.

### 2.2 SQL organization

Actual on-disk organization differs from the requested `sql/website`, `sql/ict`, `sql/staffs`, `sql/students` layout:

- `sql/system/igangaschool_system.sql` — 1.78 MB, 727 `CREATE TABLE`, 135 `INSERT`, 0 `DROP TABLE`, triggers `students_before_insert` / `students_before_update`.
- `sql/website/igangaschool_website.sql` — 706 KB, 248 `CREATE TABLE` (all 248 names also exist in the system dump), 48 `INSERT`, 3 `DROP TABLE` (used to convert `staff_trainings`, `student_dashboard_view`, `student_login_view` into views).
- `database/create_databases_and_users.sql`, `database/setup_all_databases.php`, `database/run_migrations.php` (1,995 lines, idempotent `addTable`/`addColumn`/`addIndex` helpers), `database/setup_bursar.php`.
- Root migration scripts: `db_migrate_ict_assets.php`, `db_migrate_ict_asset_categories.php`, `db_migrate_ict_tables.php`, `db_migrate_lab_manager.php`, `db_migrate_secretary_tables.php`, `db_migrate_responsive_systems.php`, `db_migrate_store_seed.php`, `migrate_transport.php`.

**Rationale decision:** preserve the two-database design actually in code (`config/database.php:6-11`) and add compatibility aliases for legacy `students`/`staff`/`ICT`/`payroll` names instead of forcing four physical databases. No tables will be dropped or overwritten.

---

## 3. Database audit

### 3.1 Connection architecture

- Canonical connector: `config/database.php` (env loader at lines 14-69, constants at 92-170, `isnm_mysqli_connect()` at 177-181, cached connection helpers at 273-333).
- Alternate connector: `includes/database_connections.php` (`DatabaseConnection` class, second DB map at 61-141).
- Legacy connector: `includes/config.php` (`$conn`, `executeQuery()` returns empty array on failure at 43-53).
- Direct `@new mysqli` fallbacks: `print_certificate.php:65-72`, `includes/document_settings.php:35-41`, database setup/migration scripts, `admin_panel/partials/{search-bus,load-bus,bus-requests,Accept-request,select_courses}.php`.
- No PDO usage; `mysqli_report(MYSQLI_REPORT_OFF)` in three files suppresses exceptions.

### 3.2 Verified local state

| Check | Result |
|---|---|
| MySQL process / port | `mysqld` running, listening on `::3306`; Apache listening on 80. |
| `.env.local` port | 3307 — no listener (error 2002). |
| Configured system/website users on 3306 | `1045 Access denied`. |
| Root (empty password) on 3306 | Connects; **no `igangaschool_*` databases exist**. |
| `getSystemConnection()` / `getWebsiteConnection()` / aliases | All return `null`. |

Consequence: every page requiring data currently falls back to defaults/empty arrays. All functional testing is blocked until step 9.1 is completed.

### 3.3 Schema issues

1. `sql/system/igangaschool_system.sql:7525-7743` creates 14 literal dotted table names (e.g. `` `igangaschool_students.bank_accounts` ``) inside the system database instead of separate schema-qualified tables.
2. 23 shared table definitions differ between the two dumps (including `students`, `staff`, `approval_requests`, `fee_structures`, `news`, `pages`, `store_requests`, `examination_records`).
3. All 248 website tables are duplicated in the system dump; relationship intent is ambiguous.
4. `sql/website/...:11532-11552` creates tables and then drops/recreates them as views (`staff_trainings`, `student_dashboard_view`, `student_login_view`).
5. Legacy student pages use columns absent from canonical `students` (`fname`, `class`, `dob`): `student_panel/index.php:187-233`, `student_panel/workspace.php:187-192`; canonical schema at `sql/system/...:18037-18117`.
6. `volunteers`, `testimonials`, `faqs` are referenced by code but absent from the website dump (only `volunteers` is added by `database/run_migrations.php:1960-1966`).
7. `dashboards/school-principal.php:20-47` runs `CREATE TABLE IF NOT EXISTS` on every page load (runtime DDL belongs in a migration).
8. `setup_all_databases.php:622-626` warns dumps contain `DROP TABLE IF EXISTS`; system dump contains zero. Documentation is stale.

### 3.4 Injection and query correctness

- **Critical:** `includes/form_router.php:94-105` builds `INSERT` column names directly from POST keys, and the public endpoint at 297-333 accepts arbitrary keys with only IP rate limiting. Values are escaped but identifiers are not validated. Fix: allowlist per route and prepared statements.
- `dashboards/system-admin.php:99-115` allowlists `$sourceTable`; `includes/module_registry.php:176-229` validates columns against `SHOW COLUMNS`; `api/student-search.php` binds parameters — these are correctly implemented.
- Silent failures are common: `execute()` results ignored/logged but callers still show success (`process-application.php:262`, `process-contact.php:59`, `process-donation.php:91`, `process-volunteer.php:68`, `PaymentService.php:321,334,352,366`).

---

## 4. Authentication, roles and access control

### 4.1 Role stores (six parallel sources)

1. `staff` + `staff_roles` tables (modern dashboards).
2. `users.role` (views/controllers stack).
3. `notice.role`.
4. `cms_role_permissions` (CMS RBAC).
5. Session keys `role`, `type`, `uid`/`user_id`/`student_id`.
6. Hard-coded role maps in panel guards.

Seeded staff roles (setup script lines 175-190): Director General, CEO, Director Academics, Director Finance, Director ICT, Director Admissions, School Principal, Deputy Principal, Academic Registrar, School Bursar, School Secretary, HR Manager, School Librarian, plus ~20 more from the SQL dump (lecturers, heads, storekeeper, wardens, etc.).

### 4.2 Critical auth defects

| # | Finding | Location |
|---|---|---|
| A1 | Student panel accepts any `uid` + `type=student` session without verifying the DB row is actually that student (DB check can only set `true`, never invalidate). | `student_panel/verifyRoleRedirect.php:15-38` |
| A2 | Bus-request approval is a GET state change; CSRF middleware only covers POST/PUT/DELETE. Any authenticated session can accept any request. | `admin_panel/partials/Accept-request.php:9-13`, `assets/config.php:31-59` |
| A3 | CMS admin has no CSRF tokens, no per-page/content permission checks, and an open redirect via POST `redirect`. | `cms/admin/index.php:43-121`, `cms/core/CMS.php:127-129,201-203` |
| A4 | Owner/admin panel fetch endpoints guarded only by generic session auth, not role. | `owner_panel/fetch-data/*`, `admin_panel/partials/*` |
| A5 | `owner_panel/student-attendence.php` lacks the owner guard include. | that file, line 1 |
| A6 | Messaging AJAX trusts client-supplied `user_id` for inbox/read/delete (IDOR). | `includes/ajax_messaging.php:122,154,187,210,237,288` |
| A7 | Student endpoints allow arbitrary student IDs: `send-request.php:8-13`, `progress-data.php:9-23`. | same files |
| A8 | Login rate limiting is inconsistent; several password reset forms have no CSRF. | `forgotPassword.php:17-23` vs `student-forgot-password.php:125-132` |
| A9 | Role checks use substring matching (`strpos`) and unseeded aliases; `Senior Lecturers` fails teacher-panel guard. | `teacher_panel/verifyRoleRedirect.php:50-51` |
| A10 | `views/*` uses one-argument `hasPermission()` against a session key login never sets, so buttons fail closed. | `config/config.php:89-95`, `views/users.php:159+` |
| A11 | `assets/config.php:104-117` defines role helpers that are never called (dead security code). | same file |
| A12 | Hardcoded DB passwords in PHP and SQL despite "env-only" comments; default admin password `admin123` exposed in setup UI. | `config/database.php:116,194-207`, `includes/database_connections.php:84-87`, `database/setup_all_databases.php:629-631` |

Password hashing uses `password_hash()` in the modern auth service; legacy setup scripts still seed simple passwords. Session expiry exists in `AuthController` but not in panel guards.

---

## 5. Public website audit

### 5.1 Page status

`php -l` passed for all 18 root public pages. Runtime rendering was not possible while DB was down.

| Page | Status | Finding |
|---|---|---|
| `index.php` | Partial | Live stats at lines 8-19; `$liveStats['staff']` loaded but never displayed; static 3000+ graduates conflicts with `history.php` 1000+. |
| `about.php`, `history.php`, `programs.php`, `organogram.php` | Static | Render; several `cursor:pointer` cards have no click handler (about 79/89/99, history 189-229/346-367, programs 244-294, contact 39-66). |
| `application.php` → `process-application.php` | Partial | 78 submitted fields; only a subset stored. Missing: religion, marital status, disability, parent/guardian, emergency contact, UCE/UACE subject grades, diploma details, motivation, declaration, intake period. `$_FILES` accessed without `isset()` at lines 100/108. Application ID uses `mt_rand()` with no uniqueness check. |
| `contact.php` → `process-contact.php` | Partial | Saves to `contact_submissions`; success shown even when insert fails; no confirmation email. |
| `donation.php` → `process-donation.php` | Partial | Saves `donations` as `pending`; no gateway call, no donor email; card fields collected but not tokenised. |
| `volunteer.php` → `process-volunteer.php` | Partial | Saves application; agreement checkbox neither validated nor stored. |
| `news.php` | Partial | Public read + admin CRUD in one file; CSRF required server-side but Summernote upload (757-772) and delete form (842-850) omit the token; `editNews()` breaks on article view. |
| `application-success.php` | Dead block | Own success message never renders because `shared/_header.php:176-193` already echoes and unsets it. |
| Department contact pages | Orphaned | Six pages exist but no navigation links reach them. |

### 5.2 Missing pages relative to requested public scope

No dedicated pages for: Admissions, Gallery, Events, Student Life, Facilities, Clinical Training, Partnerships, Downloads/Resources. `volunteer.php` exists. Administration is represented by `organogram.php`.

### 5.3 Assets, links, SEO

- 221 `href`/`src`/`action`/CSS URLs checked; only real missing local assets: `assets/css/bootstrap.min.css` referenced by `student-forgot-password.php:91` and `student-reset-password.php:87` (folder has no CSS).
- Service worker `service-worker.js:7-28` precaches missing `/css/style.css` and `/js/app.js`; `cache.addAll()` can fail, and offline fallback is unreachable because HTML requests are skipped at 65-77. Missing badge icons at 123-168. Three service workers exist (`service-worker.js`, `sw.js`, `js/service-worker.js`) with different behaviour; only `sw.js` is registered publicly.
- Motto has four variants; `shared/_header.php:77`, `shared/_footer.php:80`, `index.php:67`, `about.php:105-106`.
- `sitemap.xml` and `robots.txt` hardcode `http://localhost/ISNM/`. No canonical links or structured data on public pages.
- CMS `ContentRenderer.php` is never called by a public page; `cms/admin/index.php:13` redirects to a nonexistent `cms/staff-login.php`.
- CDN dependencies: Bootstrap 5.3.2 (public) vs 5.3.3 (setup), Font Awesome 6.5.1 vs 6.5.0, Bootstrap Icons 1.10.4 vs 1.11.3, jQuery 3.6.0 injected via `document.write` in `news.php:711`.

### 5.4 Form security

CSRF present on application/contact/donation/volunteer/news-create/auth/staff-forgot. Missing on news delete/upload, student forgot/reset, all CMS forms. `sanitizeInput()` HTML-encodes before DB insert (`includes/functions.php:5-8`), causing double escaping on output. Upload checks are extension/MIME only for applications, extension only for news images. No CAPTCHA/rate limit on public forms. Rich news content is echoed raw (`news.php:482`).

---

## 6. Dashboard and module audit

### 6.1 Dashboard inventory

138 top-level PHP files in `dashboards/` plus 6 in `dashboards/endpoints/`. Every role requested by the brief is represented, including Director General, CEO, Finance, Principal, Bursar, Registrar, Academic, ICT, HR, Admissions, Student Affairs, Examinations, Library, Procurement, Stores, Clinical Training, Department Heads, Lecturers, Staff and Students. Most query real tables; none were verified end-to-end because DB is down.

### 6.2 Broken/partial workflows

| Module | Findings |
|---|---|
| Finance | `school-bursar.php:124` inserts `category`, `director-finance.php:483` updates `expense_category`; budgets written to `budgets` (bursar) vs `budget_records` (finance); `create_budget_adjustment()` inserts a new record instead of adjusting (`director-finance.php:516-537`); payment status vocabularies differ (`Pending`/`Completed` vs `pending`/`approved`/`rejected`); receipts use `mt_rand()`; bank CSV import has no type/size validation (265-287); payroll can be marked processed with zero totals on prepare failure (163-187). |
| Payment gateway | Two stacks: `payment_gateway/` (broken: no webhook signature verification, reference from client, duplicate provider stats) and `includes/payment_gateway/PaymentService.php` (partial: prepared statements but webhook amount unchecked at 204-241, failed/cancelled statuses can overwrite, `net_amount` recorded instead of gross at 269, write failures ignored). `api/payment-callback.php:71-79` bypasses invalid signatures and still generates receipts/notifications at 86-103. **No live gateway credentials verified; no integration should be claimed as working.** |
| Academics / exams | `academic-registrar.php:336-348` marks entry without checking student/course ownership; `verify_marks()` updates any record; `approve_results()` binds int to `workflow_number`; transcript generated before verified-result check (370-394); `send_notice()` accepts arbitrary `recipient_type`. |
| Admissions | `director-admissions.php:594` "phone" filter searches reg number/name; clearance toggles accept arbitrary `requirement_type`/`new_status` and insert empty `student_name` (611-670). |
| Messaging | `dashboards/communications.php` escapes output correctly; `includes/ajax_messaging.php` IDOR (see A6). |
| Reports | `includes/reports_system.php:304-331` unescaped filename in `Content-Disposition` and CSV formula injection; `quality-assurance/qa-report.php:490-522` runs the suite twice, has no auth/CSRF, and echoes unescaped test values. |
| Legacy UI | `admin_panel/student.php:34` calls a teacher-delete function; report links `href="#"` in admin/teacher panels; `noticeboard.php:148` edit button has no handler; numerous "Something else here" dropdown placeholders. |
| Runtime DDL | `dashboards/school-principal.php:20-47` creates tables on page load. |

### 6.3 Requested-but-missing functionality

No verified end-to-end implementation was found for: clinical assessment records, academic progression rules, accommodation records in the student workflow (hostel tables exist), fee reminders as scheduled jobs, scheduled notification delivery, and versioned migration history for the 23 schema conflicts. Some capabilities exist as isolated dashboards but are not connected to student records (e.g. transcripts generated without verified marks).

---

## 7. Non-functional findings

- **Reliability:** no graceful DB-down state (pages silently show empty data); default statistics mask missing data (`index.php:8`).
- **Performance:** unbounded `SELECT *` in several dashboards; duplicated stats queries; 3 service workers; 10+ overlapping CSS files loaded per public page; jQuery loaded multiple times.
- **Responsive:** viewport and media-query CSS exist, but no browser layout test was possible before DB setup. Several `opacity:0` reveal animations depend on IntersectionObserver (`shared/app.js:56-59`), so content can remain invisible if JS fails.
- **Error handling:** PHP errors suppressed (`error_reporting(0)` in config connector); many `execute()` failures logged generically and swallowed.

---

## 8. Risk-ranked remediation plan

| Priority | Action |
|---|---|
| P0 | Provision local databases, correct env port/credentials, verify all connections. |
| P0 | Fix SQL identifier injection in `includes/form_router.php`; add prepared statements + column allowlists. |
| P0 | Remove hardcoded credentials from PHP/SQL; env-only configuration with safe local defaults; rotate deployed credentials. |
| P0 | Fix student panel session forgery, GET bus-approval CSRF, CMS CSRF/RBAC/open redirect, messaging IDOR, student endpoint IDOR. |
| P1 | Make form processors fail visibly when writes fail; complete application field persistence; add CSRF to news/student reset forms. |
| P1 | Reconcile finance table/status mismatches and payment webhook amount/idempotency checks. |
| P1 | Move runtime DDL into a migration; fix legacy student column drift; repair missing assets/service worker precache. |
| P2 | Add missing public pages, unify motto/SEO/PWA configuration, consolidate duplicate CSS/JS. |
| P2 | Complete responsive/browser testing and produce a feature-by-feature test report. |

---

## 9. Audit constraints and honesty note

- All findings above are from actual source inspection and local runtime probes, not assumptions.
- Runtime browser/DB/form testing was **not** possible at audit time because the databases did not exist locally. Those tests are scheduled after remediation and will be reported with actual results in `TEST_REPORT.md`.
- No feature in this report is claimed as tested or working on the basis of its page existing.
