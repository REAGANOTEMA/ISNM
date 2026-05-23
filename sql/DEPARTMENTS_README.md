# ISNM Staff Dashboards SQL Files

## Overview
Complete SQL database schema for all department dashboards at Iganga School of Nursing and Midwifery.

## File Structure

### Core Database Files
- `bursar_system.sql` - Financial management system
- `hr_system.sql` - Human resources management system
- `staffs/04_final_complete_staffs_database.sql` - Core staff database with all tables

### Department Dashboards
1. **05_all_departments_complete_dashboards.sql** - ALL department accounts with proper emails/passwords
2. **06_academic_registrar_dashboard.sql** - Academic records, transcripts, graduations
3. **07_nursing_department_dashboard.sql** - Clinical placements, logbooks, assessments
4. **08_midwifery_department_dashboard.sql** - Antenatal, labor, postnatal care records
5. **09_hr_manager_dashboard.sql** - Staff management, performance, recruitment
6. **10_library_dashboard.sql** - Library books, borrowing, members
7. **11_security_dashboard.sql** - Incidents, patrols, visitors, equipment
8. **12_lab_technicians_dashboard.sql** - Lab equipment, inventory, safety
9. **13_matrons_wardens_dashboard.sql** - Student welfare, health records, counseling
10. **14_director_academics_dashboard.sql** - Programs, curriculum, analytics
11. **15_director_finance_dashboard.sql** - Financial reports, budgets, student balances

### Master File
- `staffs/99_MASTER_ALL_DEPARTMENTS.sql` - Master file to run all SQLs in order

## Login Credentials

### Email Format
All department emails follow: `department@igangaschoolofnursingandmidwifery.ac.ug`

### Password Format
All passwords follow: `department@isnm`

### Department Accounts
| Department | Email | Password |
|------------|-------|----------|
| Director General | director_general@... | bursar@isnm |
| CEO | ceo@... | ceo@isnm |
| Director Academics | director_academics@... | director_academics@isnm |
| Director ICT | director_ict@... | director_ict@isnm |
| Director Finance | director_finance@... | director_finance@isnm |
| School Principal | principal@... | principal@isnm |
| Deputy Principal | deputy_principal@... | deputy_principal@isnm |
| School Bursar | bursar@... | bursar@isnm |
| Director Admissions | admissions@... | admissions@isnm |
| Academic Registrar | registrar@... | registrar@isnm |
| HR Manager | hr@... | hr@isnm |
| School Secretary | secretary@... | secretary@isnm |
| School Librarian | librarian@... | librarian@isnm |
| Head of Nursing | nursing@... | nursing@isnm |
| Head of Midwifery | midwifery@... | midwifery@isnm |
| Senior Lecturers | senior_lecturers@... | senior_lecturers@isnm |
| Lecturers | lecturers@... | lecturers@isnm |
| Matrons | matrons@... | matrons@isnm |
| Wardens | wardens@... | wardens@isnm |
| Lab Technicians | lab@... | lab@isnm |
| Drivers | drivers@... | drivers@isnm |
| Security | security@... | security@isnm |

## Student Data Integration

### Files in `students_data/`
- Excel files containing student data from multiple intake sets
- Use `universal_student_profiles` table for all student data
- Supports search by name, student number, national ID, intake set

### Student Search Features
- `search_all_students()` - Search by any criteria
- `get_all_students()` - Get all students from all intakes
- `student_search_view` - View for quick searching

### Student Profile Features
- Photo upload/update/delete
- Print profiles/reports
- Edit profile information
- Track all changes via `student_profile_edits`

## Running the SQL Files

### Option 1: Run Master File
```bash
mysql -u username -p < sql/staffs/99_MASTER_ALL_DEPARTMENTS.sql
```

### Option 2: Run Individual Files
```bash
mysql -u username -p igangaschoolofl_staffs_db < sql/bursar_system.sql
mysql -u username -p igangaschoolofl_staffs_db < sql/hr_system.sql
mysql -u username -p igangaschoolofl_staffs_db < sql/staffs/04_final_complete_staffs_database.sql
# ... continue with other files
```

## Database Names
- Main staff database: `igangaschoolofl_staffs_db`
- Bursar database: `igangaschoolofl_students_db`
- Uses database from bursar_system.sql for compatibility