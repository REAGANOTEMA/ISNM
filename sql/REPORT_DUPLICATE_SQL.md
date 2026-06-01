Duplicate SQL artifacts scan - summary

Found duplicate or overlapping schema definitions:

- `announcements` table
  - Defined in: `sql/website/01_create_website_database.sql`, `database/isnm_complete_schema.sql`
  - Recommendation: Keep website-specific definition in `sql/website/01_create_website_database.sql` and remove or consolidate the entry in `database/isnm_complete_schema.sql` to avoid conflicting migrations.

- `payments`, `student_invoices`, `proof_of_payments`
  - Defined in: `financial_tables.sql`
  - Recommendation: Treat `financial_tables.sql` as authoritative for financial schema. Remove duplicate fragments elsewhere.

- `staff` and related tables
  - Major definition: `sql/staffs/04_final_complete_staffs_database.sql` (comprehensive)
  - Smaller or duplicate fragments: `sql/staffs/05_all_departments_complete_dashboards.sql`, `sql/archived/hr_system.sql`
  - Recommendation: Use `04_final_complete_staffs_database.sql` as canonical. Archive or remove partial/older files (move to `sql/archived/`) to avoid accidental re-application.

Notes & next steps:
- I created `sql/staffs/99_drop_staff_foreign_keys.sql` to help drop FK constraints referencing the `staff` table dynamically. Review and set `@target_db` before running.
- If you want, I can consolidate the duplicates now by removing duplicate CREATE statements and updating the master migration script.
- If you plan to drop the `staff` table, run `sql/staffs/99_drop_staff_foreign_keys.sql` first, review constraints listed by the alternative query in the file, then run the DROP TABLE statement.
