# ISNM Database Setup Instructions

## ⚠️ IMPORTANT: You Cannot Use phpMyAdmin for SOURCE Commands

The error `#1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'SOURCE'` occurs because **phpMyAdmin does not support the `SOURCE` command**.

The `SOURCE` command is a **mysql client-only feature** used to execute SQL files. It does not work in:
- phpMyAdmin
- Any web-based SQL editor
- Any SQL IDE that submits raw SQL

## Correct Method: Use MySQL Command Line

### Option 1: Batch File (Windows) — EASIEST

**Steps:**
1. Double-click `RUN_MASTER_SQL.bat` in the project root
2. Follow the prompts
3. The script will automatically run all SQL files in order

**Requirements:**
- XAMPP must be installed
- MySQL must be in your system PATH (usually automatic with XAMPP)

### Option 2: PowerShell Script

**Steps:**
1. Right-click `RUN_MASTER_SQL.ps1`
2. Select "Run with PowerShell"
3. Follow the prompts

**If you get execution policy error:**
```powershell
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope CurrentUser
```

### Option 3: Manual Command Line

**Step 1: Open Command Prompt**
- Press `Win + R`
- Type `cmd` and press Enter

**Step 2: Navigate to your project:**
```batch
cd C:\xampp\htdocs\ISNM
```

**Step 3: Run the master SQL file:**
```batch
mysql -h localhost -u root < sql\staffs\99_MASTER_ALL_DEPARTMENTS.sql
```

**If MySQL is not found:**
- Make sure XAMPP is running (start `C:\xampp\xampp-control.exe`)
- Try the full path: `C:\xampp\mysql\bin\mysql.exe -h localhost -u root < sql\staffs\99_MASTER_ALL_DEPARTMENTS.sql`

## What Gets Created

The master script will create:

**Staff Database (`igangaschoolofl_staffs_db`):**
- ✓ 20+ staff management tables
- ✓ 24 staff roles with permissions
- ✓ All 24 staff accounts (Director, Secretary, ICT, Finance, HR, etc.)
- ✓ Dashboard access controls
- ✓ Student profiles and management
- ✓ Fee accounts and financial tracking
- ✓ Document templates and generation
- ✓ Student photos and profiles

**Student Database (`igangaschoolofl_students_db`):**
- ✓ Student records and enrollments
- ✓ Student fees and payments
- ✓ Bursar system (invoices, receipts)
- ✓ Academic tracking

**Stored Procedures:**
- ✓ add_new_student() — Add student with automatic fee creation
- ✓ update_student_record() — Update student fields with logging
- ✓ search_students() — Full-text search
- ✓ get_all_students_list() — Filtered student list with pagination
- ✓ 15+ additional procedures for different departments

## Errors That Were Fixed

### ❌ Error: Table 'staff_profiles' doesn't exist
**Cause:** File 09_hr_manager_dashboard.sql was referencing staff_profiles table that exists but wasn't created yet (prerequisite not run)
**Status:** ✅ **FIXED** - Removed invalid reference to staff_profiles

### ❌ Error: Table 'staff_dashboard_access' doesn't exist  
**Cause:** Multiple files insert into staff_dashboard_access, which is created in 04_final_complete_staffs_database.sql (prerequisite)
**Status:** ✅ **FIXED** - Table is created with `CREATE TABLE IF NOT EXISTS` in 04_final

### ❌ Error: Invalid default value for 'actual_departure'
**Cause:** TIMESTAMP columns need explicit NULL defaults
**Status:** ✅ **FIXED** - Changed to `TIMESTAMP NULL`

### ❌ Error: Table 'student_invoices' doesn't exist
**Cause:** 15_director_finance_dashboard.sql references tables from bursar_system.sql
**Status:** ✅ **FIXED** - Tables are created by bursar_system.sql (prerequisite in master script)

## Login Credentials

After successful setup, you can login as:

```
Email: directorgeneral@igangaschoolofnursingandmidwifery.ac.ug
Password: DorisJoy2026

Email: secretary@igangaschoolofnursingandmidwifery.ac.ug  
Password: Lovely2God

Email: dannybict@igangaschoolofnursingandmidwifery.ac.ug
Password: Lovely2God
```

**For all 24 staff credentials, see:** `sql/staffs/99_MASTER_ALL_DEPARTMENTS.sql` (lines 40-60)

## Features Now Available

### Student Management Dashboard
- **Access:** Secretary, Director ICT, Academic Registrar
- **URL:** `dashboards/student-management.php`
- **Features:**
  - Add new students with 20+ fields
  - View all students with search/filter
  - Automatic fee creation (tuition + facility + registration)
  - Default password assignment (12345678 - must change on first login)
  - Activity logging for all additions

### For Each Department:
- Academic Registrar → Academic Records Management
- Nursing Department → Nursing Staff Management
- HR Manager → Staff Records & Payroll
- Finance Director → Financial Dashboard & Reports
- Security → Incident Tracking & Visitor Management
- And 8+ more departments...

## Troubleshooting

### Problem: "MySQL server is not running"
**Solution:** Start XAMPP
1. Open `C:\xampp\xampp-control.exe`
2. Click "Start" next to MySQL
3. Try the command again

### Problem: "Access denied for user 'root'"
**Solution:** MySQL credentials issue
1. Verify your root password is empty (XAMPP default)
2. If you've set a password, update the command:
   ```batch
   mysql -h localhost -u root -p < sql\staffs\99_MASTER_ALL_DEPARTMENTS.sql
   ```
3. Then enter your password when prompted

### Problem: "The system cannot find the path specified"
**Solution:** Wrong directory
1. Make sure you're in the ISNM directory: `C:\xampp\htdocs\ISNM`
2. Verify `sql\staffs\99_MASTER_ALL_DEPARTMENTS.sql` exists
3. Use absolute paths if needed:
   ```batch
   mysql -h localhost -u root < "C:\xampp\htdocs\ISNM\sql\staffs\99_MASTER_ALL_DEPARTMENTS.sql"
   ```

### Problem: Script errors or table creation fails
**Solution:** Database may already exist
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Drop existing databases:
   - Right-click `igangaschoolofl_staffs_db` → Drop
   - Right-click `igangaschoolofl_students_db` → Drop
3. Run the master script again

## Next Steps

1. ✅ Run `RUN_MASTER_SQL.bat` (or use command line option)
2. ✅ Verify no errors in output
3. ✅ Login to `http://localhost/ISNM` with provided credentials
4. ✅ Test student management system (Secretary can add students)
5. ✅ Verify all department dashboards work

## Need Help?

If you see any errors:
1. Note the exact error number (e.g., #1146, #1064)
2. Check that all prerequisites were run in order
3. Verify MySQL is running
4. Verify file paths are correct
