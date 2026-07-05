# SMIS Enterprise Audit & Rebuild Tracker

## Security (in progress)
- [x] Fix open redirect risk in `auth-handler.php` (allowlist internal redirects only)
- [ ] Enforce CSRF consistently across protected pages + AJAX
- [ ] Verify role-based access checks are consistent across:
  - `security-middleware.php`
  - `auth-service.php`
  - `auth-handler.php`
  - `includes/functions.php` (`hasPermission()`)

## DB & Schema Consistency
- [ ] Audit ALL SQL files under `sql/website`, `sql/ict`, `sql/staffs`, `sql/students`
- [ ] Verify every referenced table/column exists (PK/FK, indexes, joins)
- [ ] Reconcile naming/key mismatches (`students.id` vs `student_id`, status values, etc.)

## Module & Dashboard Standardization
- [ ] Create/verify a single enterprise layout template for dashboards
- [ ] Ensure all staff dashboards (~25) use the same layout components
- [ ] Ensure backend RBAC blocks unauthorized modules (not UI-only)

## QA / Validation Gates
- [ ] Run through critical flows:
  - Staff login -> dashboard -> CRUD module
  - Student login -> first-login password setup -> student portal modules
  - Notifications + exports + print
- [ ] Confirm no PHP notices/warnings and no JS console errors
