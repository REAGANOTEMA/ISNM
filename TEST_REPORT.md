# ISNM Test Report

**Project:** Iganga School of Nursing and Midwifery website and management system
**Location:** `C:\xampp\htdocs\ISNM`
**Date:** 2026-11-27
**Environment:** Local XAMPP — Apache 2.4.58, PHP 8.0.30, MariaDB 10.4.32
**URL:** `http://localhost/ISNM`

---

## 1. Environment and connectivity

| Check | Command / Method | Result |
|---|---|---|
| MySQL service | Started `C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini` | Running on TCP 3306 |
| Apache service | Started `C:\xampp\apache\bin\httpd.exe` | Running on port 80 |
| PHP CLI | `C:\xampp\php\php.exe -v` | PHP 8.0.30 |
| Databases | `SHOW DATABASES` | `igangaschool_system`, `igangaschool_website` exist |
| `getSystemConnection()` | PHP CLI via `config/database.php` | OK |
| `getWebsiteConnection()` | PHP CLI via `config/database.php` | OK |
| `getStudentsConnection()` | PHP CLI via `config/database.php` | OK |
| `getStaffConnection()` | PHP CLI via `config/database.php` | OK |
| `getICTConnection()` | PHP CLI via `config/database.php` | OK |
| `getPayrollConnection()` | PHP CLI via `config/database.php` | OK |

Database credentials were read from `.env.local`; they are not reproduced here.

---

## 2. Static checks

| Check | Scope | Result |
|---|---|---|
| `php -l` | All project PHP files | 683 files checked, 0 syntax errors |
| File count | PHP files | 683 (includes new migrations and helper) |
| Git status | Working tree | Reviewed; no commit created by this work |

---

## 3. HTTP page sweeps

### 3.1 Public/root pages

Method: GET each root-level `.php` file, excluding POST processors and the temporary QA helper; flag non-200 statuses and PHP/system error markers.

| Result | Count |
|---|---|
| Files checked | 88 |
| HTTP 200 with no error marker | 86 |
| Intentional non-200 | 2 |
| Broken pages | 0 |

Intentional non-200 responses:
- `forgotPassword.php` — HTTP 401, JSON response
- `health-check.php` — HTTP 403, deliberately protected

Evidence: `C:\Users\REAGAN~1\AppData\Local\Temp\opencode\public_pages_test.csv`

### 3.2 Dashboards

Method: authenticate as `Director General`, GET every file in `dashboards/`, flag non-200 statuses and embedded PHP/system error markers.

| Result | Count |
|---|---|
| Dashboards checked | 138 |
| HTTP 200 with no error marker | 138 |
| Failed | 0 |

A first sweep flagged five dashboards because of embedded “System Error” HTML. Root cause: undefined `csrf_token()` in `includes/website_submissions_widget.php:116,124`. After replacing it with `htmlspecialchars($_SESSION['csrf_token'] ?? '')`, the final sweep passed 138/138.

Evidence: `C:\Users\REAGAN~1\AppData\Local\Temp\opencode\dash_test_final.csv`

### 3.3 Local assets

Method: extract local CSS/JS/image URLs from key public pages and issue HEAD requests.

| Result | Count |
|---|---|
| Local assets checked | 43 |
| Broken | 0 |

Evidence: `C:\Users\REAGAN~1\AppData\Local\Temp\opencode\test_assets.ps1`

---

## 4. Form submission tests

All test records used `qa-test@isnm.local` and were deleted after verification.

| Form | Endpoint | Result |
|---|---|---|
| Contact | `process-contact.php` | Success alert displayed on redirect; 4 rows saved with unique auto-increment ids |
| Volunteer | `process-volunteer.php` | Success flow completed; 2 rows saved with unique auto-increment ids |
| Application | `process-application.php` | Alert: `Application submitted successfully! Your Application ID is: ISNM20266359. Please save this ID for future reference.`; row saved as `Pending` with `program_applied = Nursing` |

Application row verified in `igangaschool_website.student_applications`:
- `id = 1`
- `application_number = ISNM20266359`
- `email = qa-test@isnm.local`
- `status = Pending`

Post-migration verification confirmed repeated contact/volunteer submissions received unique auto-increment ids (`1`, `2`, …) instead of colliding at `id = 0`.

---

## 5. Student account creation and login tests

### 5.1 Role policy

`isnm_student_creation_allowed($role)` was exercised via PHP CLI.

| Role | Result |
|---|---|
| Director General | ALLOW |
| CEO | ALLOW |
| System Administrator | ALLOW |
| Admin | ALLOW |
| School Secretary | ALLOW |
| Secretary | ALLOW |
| Academic Registrar | ALLOW |
| Registrar | ALLOW |
| Director Admissions | ALLOW |
| Admissions Officer | ALLOW |
| Director ICT | ALLOW |
| ICT Officer | ALLOW |
| Computer Lab Manager | ALLOW |
| School Principal | ALLOW |
| School Bursar | ALLOW |
| Lecturer | DENY |
| Storekeeper | DENY |
| Student | DENY |

### 5.2 HTTP creation test

Method: temporary staff session per role, CSRF token from session, POST `action=create_student` to `auth-handler.php`.

| Role | Result |
|---|---|
| Director General | Student account created; login ID and temporary password returned |
| School Secretary | Student account created |
| Academic Registrar | Student account created; login ID and temporary password returned |
| Director ICT | Student account created |
| Lecturer | Denied; no account created |

All created rows had:
- unique `id`
- non-empty `index_number`
- `full_name`
- bcrypt `password` hash
- `is_first_login = 1`

### 5.3 Student login test

Method: POST `action=student_login` to `auth-handler.php` with a generated login ID and temporary password.

| Check | Result |
|---|---|
| Redirect after login | `dashboards/student-portal.php` |
| `dashboards/student-portal.php` | HTTP 200 |
| Session authenticated | Yes |

The temporary staff-session helper `_qa_login.php` was removed after testing. Generated QA student accounts were deleted from the database.

---

## 6. Database integrity migrations

| Migration | Result |
|---|---|
| `2026_11_27_critical_integrity.php` | Applied successfully; rerun idempotent |
| `2026_11_27_auto_increment_keys.php` | 86/89 system and 193/193 website `id` keys converted; 3 system tables needed residue handling |
| `2026_11_27_auto_increment_residue.php` | `academic_programs`, `activity_log`, `admission_requirements` fixed |
| `2026_11_27_primary_keys.php` | 620 system and 3 website keyless tables fixed; `fee_payments` needed a separate `updated_at` default fix |
| Post-check | 0 integer `id` primary keys remain without `AUTO_INCREMENT`; the only remaining `id` columns without auto-increment are intentional `varchar` keys in `admins` tables and two legacy tables using `varchar` ids |

Backups:
- `C:\Users\REAGAN~1\AppData\Local\Temp\opencode\backup\isnm_before_migration.sql`
- `C:\Users\REAGAN~1\AppData\Local\Temp\opencode\backup\isnm_before_pk_migration.sql`

No original record was deleted. Duplicate/NULL ids were re-numbered where necessary to add primary keys.

---

## 7. Limitations / not tested

- No live payment gateway credentials were available; payment callbacks/webhooks were not tested end-to-end.
- No email/SMS delivery was tested.
- No production deployment was available; all results are local XAMPP only.
- The known README password `ReagaN23#` did not match stored staff hashes; a temporary session helper was used for role tests and then removed.
- Browser-based visual/responsive verification for a premium redesign was not performed; the existing UI/CSS remains.
- Security findings listed as P0/P1 in `AUDIT_REPORT.md` remain open unless explicitly marked fixed there.
