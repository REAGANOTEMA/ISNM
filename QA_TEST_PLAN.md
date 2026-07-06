# ISNM QA Test Plan — Phase 11

## Scope: All 29 Staff Dashboards + Student Portal + Auth Flows

---

## 1. STAFF AUTHENTICATION FLOW

### 1.1 Login
- [ ] Staff login page loads without errors
- [ ] Invalid email shows error message
- [ ] Invalid password shows error message
- [ ] Locked account (5 failed attempts) shows error message
- [ ] Valid credentials redirect to dashboard
- [ ] First-login account (is_first_login=1) redirects to staff-force-password-change.php
- [ ] Session persists across page navigation
- [ ] Logout destroys session and redirects to login

### 1.2 Force Password Change
- [ ] New password < 8 chars shows validation error
- [ ] Non-matching passwords show error
- [ ] Valid password update succeeds and redirects to dashboard
- [ ] After password change, is_first_login is 0 in DB
- [ ] Navigating to dashboard URL before password change redirects to force-change page
- [ ] `staff-force-password-change.php` shows logged-in user's name

### 1.3 Session Security
- [ ] Session expires after 1 hour of login
- [ ] Session expires after 20 minutes of inactivity
- [ ] User-Agent mismatch triggers session destruction
- [ ] CSRF token validates on POST requests
- [ ] AJAX POST requests accept X-CSRF-Token header

### 1.4 Password Reset
- [ ] Password reset page loads
- [ ] Invalid email shows error
- [ ] Valid email generates reset token
- [ ] Token-based password reset works
- [ ] Reset link expires after 1 hour

---

## 2. STUDENT AUTHENTICATION

- [ ] Student login page loads
- [ ] Index number lookup works
- [ ] Password verification succeeds
- [ ] First-login student redirected to password setup
- [ ] Student portal loads data correctly

---

## 3. ALL 29 STAFF DASHBOARDS

For each dashboard below, verify:
- [ ] Page loads without errors (no blank screens, no PHP warnings)
- [ ] Sidebar navigation items match the role's permitted pages
- [ ] All data tables populate with database content
- [ ] All charts/statistics render numeric values (not 0 or empty)
- [ ] All action buttons/links point to existing files (no 404)
- [ ] POST forms include CSRF token
- [ ] AJAX endpoints return valid JSON
- [ ] Role-based access enforced (no unauthorized data visible)

### Dashboard List

| # | Dashboard | File | Key Sections |
|---|-----------|------|-------------|
| 1 | Director General | `director-general.php` | System overview, health, all-institution stats |
| 2 | CEO | `ceo.php` | Institution management, organogram setup |
| 3 | Director Academics | `director-academics.php` | Academic programs, course catalog |
| 4 | Director Finance | `director-finance.php` | Financial reports, fee structures |
| 5 | Director ICT | `director-ict.php` | System health, ICT assets |
| 6 | Director Admissions | `director-admissions.php` | Applications, admissions pipeline |
| 7 | School Principal | `school-principal.php` | Academic oversight, departments |
| 8 | Deputy Principal | `deputy-principal.php` | Discipline, staff supervision |
| 9 | Academic Registrar | `academic-registrar.php` | Student enrollments, transcripts |
| 10 | Head of Nursing | `head-nursing.php` | Nursing department, clinical placements |
| 11 | Head of Midwifery | `head-midwifery.php` | Midwifery department, clinical logbooks |
| 12 | Senior Lecturer | `senior-lecturers.php` | Course management, exam marks, reports |
| 13 | Lecturer | `lecturers.php` | Attendance, marks, lesson plans, assignments |
| 14 | Librarian | `school-librarian.php` | Library catalog, borrowing |
| 15 | Matron/Hostel Warden | `matrons.php` | Hostel allocations, student welfare |
| 16 | Bursar | `school-bursar.php` | Billing, payments, ledger, tax, payroll |
| 17 | HR Manager | `hr-manager.php` | Staff records, contracts, onboarding |
| 18 | Secretary | `school-secretary.php` | Correspondence, admin support |
| 19 | Storekeeper | `storekeeper.php` | Inventory, stock requests |
| 20 | Security | `security.php` | Access logs, incident reports |
| 21 | Sickbay/Nurse | `sickbay.php` | Daily sick records, medicine stock |
| 22 | Lab Technician | `skills-lab.php` | Lab inventory, equipment |
| 23 | Computer Lab | `computer_lab.php` | Lab bookings, equipment tracking |
| 24 | IT Support | `it-support-tickets.php` | Support tickets, system monitoring |
| 25 | Chemical Inventory | `chemical-inventory.php` | Chemical stock, safety records |
| 26 | Lab Booking | `lab-booking-management.php` | Lab schedule, availability |
| 27 | Drivers | `drivers.php` | Vehicle logs, trip records |
| 28 | Student Discipline | `student-discipline.php` | Case management, sanctions |
| 29 | Guild President | `guild-president.php` | Welfare, events, counseling, sports |

---

## 4. DATABASE VERIFICATION

- [ ] `igangaschoolofl_staffs_db` — all tables present and populated
- [ ] `igangaschoolofl_students_db` — all tables present and populated
- [ ] `igangaschoolofl_website_db` — all 47 tables present
- [ ] `igangaschoolofl_ict` — all ICT tables present
- [ ] `fee_payments` — exists as BASE TABLE (not VIEW)
- [ ] `staff_audit_logs` — exists and logs activity
- [ ] Staff table has `is_first_login`, `password_changed` columns
- [ ] Cross-database queries work (staff JOIN students JOIN website)

---

## 5. SECURITY CHECKS

- [ ] All default passwords randomized (no isnm2026, bursar@isnm)
- [ ] Staff dashboard redirects use relative paths (no open redirect)
- [ ] `director-admissions.php` uses prepared statements for export_csv
- [ ] `auth-functions.php` reset token stored in password_resets table
- [ ] `staff-login.php` uses `password_verify()` not comparing hashes
- [ ] `staff_dashboard_access.php` enforces RBAC with word-boundary matching
- [ ] No hardcoded credentials in PHP files (in .env only)

---

## 6. PERFORMANCE

- [ ] `fetchResultList.php` uses JOIN (no N+1)
- [ ] `downloadMarks.php` uses JOIN (no N+1)
- [ ] `lab-booking-management.php` uses batched GROUP BY (not 54 queries)
- [ ] `student_data_loader.php` uses single `array_merge(...)` (not O(n²))
- [ ] Dashboard pages load within 5 seconds
- [ ] AJAX endpoints respond within 2 seconds

---

## 7. ERROR HANDLING

- [ ] 404 / missing page shows graceful error (not blank screen)
- [ ] Database connection failure shows "Database unavailable"
- [ ] PHP fatal errors show "Internal Server Error" page (from shutdown handler)
- [ ] Error logs are written to PHP error_log for all catch blocks
- [ ] Silent return catch blocks now log before returning
