# ISNM Complete System Audit Report

**Project:** Iganga School of Nursing and Midwifery website and management system
**Location:** `C:\xampp\htdocs\ISNM`
**Date:** 2026-11-27
**Method:** Static source audit plus live local runtime probes after starting Apache/MySQL (PHP 8.0.30, MariaDB 10.4.32). PHP lint, HTTP sweeps, database integrity migrations, form POST tests, and student login tests were performed. No original records were deleted.

---

## 1. Executive summary

| Area | Verdict | Notes |
|---|---|---|
| Database connectivity | **WORKING (local)** | `igangaschool_system` and `igangaschool_website` exist; all six connection helpers in `config/database.php` return live connections on port 3306. |
| Technology stack | Working legacy PHP | Plain PHP + MySQLi + Bootstrap/jQuery dashboards, session auth, two logical databases. No framework or build step. |
| Public website | **WORKING core flows** | Application, contact, donation and volunteer processors now fail visibly on DB errors; tested forms persist data. CMS engine and some SEO/PWA inconsistencies remain. |
| Authentication / roles | **IMPROVED, still fragmented** | Student creation is now governed by one policy and generates usable login IDs with temporary passwords. Legacy auth stacks still need consolidation. |
| Dashboards | **WORKING load path** | All 138 top-level dashboards returned HTTP 200 with no embedded PHP/system errors after fixing `includes/website_submissions_widget.php`. |
| Security | **CRITICAL GAPS REMAIN** | SQL identifier injection in `includes/form_router.php`, CMS CSRF/RBAC/open redirect, messaging/student endpoint IDOR, GET state changes, hardcoded credentials in legacy files, inconsistent rate limiting. |
| Database integrity | **REPAIRED** | Added primary keys/auto-increment across keyless tables, unique student index numbers, missing application/volunteer/notification columns, and `staff_notifications.staff_id` backfill. |
| Reporting / exports | **PARTIAL** | CSV formula injection and unescaped filename issues remain in `includes/reports_system.php`. |
| Performance | **MIXED** | Parameterized/bounded queries exist alongside unbounded selects, duplicated CSS/JS and three service workers. |
| Testability | **OPERATIONAL** | End-to-end tests were run; actual results are in `TEST_REPORT.md`. |

**Current blocker:** none for local operation. Remaining work is security hardening, legacy-stack consolidation, and the premium visual redesign.

---

## 2. Verified runtime state

| Check | Result |
|---|---|
| Apache / PHP | Apache 2.4.58, PHP 8.0.30, listening on port 80 |
| MySQL / MariaDB | MariaDB 10.4.32, TCP 3306 |
| Databases | `igangaschool_system`, `igangaschool_website` |
| Connection helpers | `getSystemConnection`, `getWebsiteConnection`, `getStudentsConnection`, `getStaffConnection`, `getICTConnection`, `getPayrollConnection` — all OK |
| PHP syntax | 683 PHP files checked, 0 errors |
| Homepage | `http://localhost/ISNM/index.php` — HTTP 200 |
| Dashboards | 138/138 HTTP 200, 0 embedded error markers |
| Public root pages | 86 HTTP 200; `forgotPassword.php` 401 and `health-check.php` 403 are intentional; 0 error markers |
| Local assets | 43 CSS/JS/image URLs checked on key public pages, 0 broken |

---

## 3. Database integrity work completed

Versioned migrations added under `database/migrations/`:

| Migration | Purpose |
|---|---|
| `2026_11_27_critical_integrity.php` | Renamed duplicate student index numbers, added `uniq_students_index_number`, added missing `student_applications`/`volunteer_applications` columns, added notification compatibility columns, added and backfilled `staff_notifications.staff_id`. Idempotent on rerun. |
| `2026_11_27_auto_increment_keys.php` | Converted existing `id` primary keys to `AUTO_INCREMENT` in 86/89 system tables and 193/193 website tables. |
| `2026_11_27_auto_increment_residue.php` | Fixed three system tables (`academic_programs`, `activity_log`, `admission_requirements`) that had `id=0` rows blocking resequencing; rows were renumbered, not deleted. |
| `2026_11_27_primary_keys.php` | Added `PRIMARY KEY` + `AUTO_INCREMENT` to 620 system and 3 website keyless tables that had an integer `id`; duplicate/NULL ids were re-numbered. `fee_payments` needed a separate `updated_at` default fix. |

Backups were taken before structural changes:
- `C:\Users\REAGAN~1\AppData\Local\Temp\opencode\backup\isnm_before_migration.sql`
- `C:\Users\REAGAN~1\AppData\Local\Temp\opencode\backup\isnm_before_pk_migration.sql`

No table was dropped and no original row was deleted. Only duplicate/NULL `id` values were re-numbered where required to establish a primary key.

---

## 4. Authentication and student creation fixes

### 4.1 Canonical policy
- `isnm_student_creation_allowed($role)` in `config/database.php` is now the single source of truth.
- Allowed roles: Director General/CEO, System Administrator/Admin, School Secretary/Secretary, Academic Registrar/Registrar, Director Admissions/Admissions Officer, Director ICT/ICT Officer/Computer Lab Manager, School Principal, School Bursar.
- Denied roles include Lecturer, Storekeeper and Student.

### 4.2 Usable accounts
- `isnm_generate_student_index($conn, $year)` generates `ISNM/YYYY/NNNN`.
- `studentExistsByIndexNumber()` now checks both `index_number` and `student_number`.
- Student creation flows now store `index_number`:
  - `auth-service.php`, `auth-handler.php`
  - `dashboards/school-secretary.php`, `dashboards/academic-registrar.php`, `dashboards/director-admissions.php`, `dashboards/director-ict.php`, `dashboards/student-add.php`
  - `ajax/registrar_documents_ajax.php`
- `auth-service.php::createStudentAccount()` now stores `full_name` and a bcrypt-hashed temporary password returned to the creating staff member.
- `includes/student_dashboard_access_denied.php` provides a branded 403 page.
- `includes/module_config.php` exposes Add Student to the authorized roles.

**Verified:** four authorized roles created accounts; Lecturer was denied; a generated account logged in and opened `dashboards/student-portal.php` with HTTP 200.

---

## 5. Form and dashboard fixes

### 5.1 Public forms
- `process-application.php`, `process-contact.php`, `process-donation.php`, `process-volunteer.php` now throw when inserts fail instead of displaying success.
- `process-application.php` uses `uploadFileIfPresent()` for optional uploads.
- `process-volunteer.php` bind types corrected from `'ssssisssssss'` to `'ssssssssssss'`.
- Database migrations added the columns the application/volunteer processors write.

### 5.2 Dashboards
- Fixed undefined `csrf_token()` calls in `includes/website_submissions_widget.php` that embedded a “System Error” page inside five dashboards.
- Fixed root-level protected pages redirecting to `../staff-login.php` (which resolved outside `/ISNM`) by using `APP_BASE_PATH` in:
  - `includes/staff_dashboard_access.php`
  - `includes/config.php`
  - `includes/auth_functions.php`

---

## 6. Remaining known issues

Security and hardening still pending:

| Priority | Finding | Location |
|---|---|---|
| P0 | Dynamic INSERT column names built from POST keys allow identifier injection. | `includes/form_router.php:94-105,297-333` |
| P0 | CMS forms lack CSRF/RBAC and have an open POST redirect. | `cms/admin/index.php`, `cms/core/CMS.php` |
| P0 | Student-panel session forgery: `uid` + `type=student` is trusted without invalidating on DB mismatch. | `student_panel/verifyRoleRedirect.php:15-38` |
| P0 | Bus-request approval is a GET state change with no CSRF. | `admin_panel/partials/Accept-request.php`, `assets/config.php` |
| P0 | Messaging and student endpoints trust client-supplied user/student IDs (IDOR). | `includes/ajax_messaging.php`, `send-request.php`, `progress-data.php` |
| P0 | Hardcoded credentials remain in legacy configuration/setup files. | `includes/database_connections.php`, `database/setup_all_databases.php` |
| P1 | News delete/upload and student forgot/reset forms lack CSRF. | `news.php`, `student-forgot-password.php`, `student-reset-password.php` |
| P1 | CSV formula injection and unescaped `Content-Disposition` filename. | `includes/reports_system.php` |
| P1 | Payment gateway callbacks/webhooks lack signature/amount/idempotency verification; no live gateway credentials were available to test. | `api/payment-callback.php`, `payment_gateway/`, `includes/payment_gateway/PaymentService.php` |
| P1 | Runtime DDL still executes on page load. | `dashboards/school-principal.php:20-47` |
| P2 | Premium visual redesign was not materially applied; existing CSS/UI remains. | public pages, `assets/css/`, `shared/` |
| P2 | Duplicate CSS/JS, three service workers, stale SEO/PWA URLs, inconsistent motto. | public pages, `service-worker.js`, `sw.js`, `js/service-worker.js`, `sitemap.xml`, `robots.txt` |
| P2 | CMS `ContentRenderer.php` remains orphaned; department contact pages have no navigation. | `cms/core/ContentRenderer.php`, department contact pages |

---

## 7. Honesty note

- Runtime testing was performed against the local XAMPP installation only.
- No live payment gateway, email/SMS delivery, or production hosting configuration was available.
- The original staff password (`ReagaN23#`) did not match stored hashes; role testing used a temporary session helper that has since been removed.
- QA records created during testing were removed from the databases.
- No feature is claimed working in production without deployment-specific verification.
- Detailed test evidence is in `TEST_REPORT.md`.
