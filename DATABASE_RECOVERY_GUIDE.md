# ISNM Database Recovery Guide

## Overview

This guide provides comprehensive instructions to fix the "Database unavailable" error and recover the ISNM system. Multiple tools have been created to diagnose and repair database connectivity issues.

## Quick Start

If you're experiencing a "Database unavailable" error, follow these steps:

### 1. **Immediate Action: Run Master Database Repair**

The master repair script will:
- ✓ Verify MySQL is running
- ✓ Create all required databases
- ✓ Import SQL schemas
- ✓ Validate all connections
- ✓ Check data integrity

```bash
# Navigate to project root
cd c:\xampp\htdocs\ISNM

# Run with full repair (recommended first time)
C:\xampp\php\php.exe master_database_repair.php --import-sql --verbose

# Output:
# ╔════════════════════════════════════════════╗
# ║  ISNM Master Database Repair & Initialize  ║
# ║  This tool will fix database unavailability ║
# ╚════════════════════════════════════════════╝
```

### 2. **Verify Setup with Health Check**

```bash
C:\xampp\php\php.exe health-check.php
```

This will verify:
- PHP version and extensions
- Required files present
- All databases connected
- Directory permissions

### 3. **Access the System**

Once the scripts show success, the system should be accessible:
- Staff Portal: `http://localhost/ISNM/staff-login.php`
- Student Portal: `http://localhost/ISNM/student-login.php`
- Admin Dashboard: `http://localhost/ISNM/dashboard.php`

---

## Tools Available

### 1. **master_database_repair.php** (Primary Tool)

**Purpose:** Complete database recovery and initialization

**Usage:**
```bash
C:\xampp\php\php.exe master_database_repair.php [OPTIONS]

Options:
  --import-sql        Import SQL schemas into databases (recommended)
  --skip-validation   Skip schema validation checks
  --verbose          Show detailed diagnostics
  --help            Show this help message
```

**What it does:**
1. Connects as root user to MySQL
2. Creates all four databases
3. Grants necessary permissions
4. Imports SQL schemas from sql/ directory
5. Validates all connections
6. Verifies all tables exist

**Example:**
```bash
# Full repair with detailed output
C:\xampp\php\php.exe master_database_repair.php --import-sql --verbose

# Skip validation (faster)
C:\xampp\php\php.exe master_database_repair.php --import-sql --skip-validation
```

---

### 2. **database_init.php** (Initialization Tool)

**Purpose:** Initialize and validate database connections

**Usage:**
```bash
C:\xampp\php\php.exe database_init.php [OPTIONS]

Options:
  --check-only  Only check connections without repairs
  --repair      Attempt to repair database issues
  --verbose     Show detailed information
```

**What it does:**
- Checks each database connection
- Counts tables in each database
- Attempts repairs if connections fail

---

### 3. **database_schema_manager.php** (Schema Validator)

**Purpose:** Validate and synchronize database schema

**Usage:**
```bash
C:\xampp\php\php.exe database_schema_manager.php [OPTIONS]

Options:
  --verbose       Show detailed table information
  --repair        Attempt schema repairs
  --sync          Synchronize schema from SQL files
  --database=NAME Target specific database (students|staffs|website|ict)
```

**What it does:**
- Validates schema against SQL files
- Identifies missing tables
- Reports orphaned tables
- Can auto-create missing tables from SQL files

**Example:**
```bash
# Check Students database only
C:\xampp\php\php.exe database_schema_manager.php --database=students --verbose

# Sync all schemas
C:\xampp\php\php.exe database_schema_manager.php --sync
```

---

### 4. **module_fixer.php** (Module Analyzer)

**Purpose:** Analyze modules for database compatibility issues

**Usage:**
```bash
C:\xampp\php\php.exe module_fixer.php [OPTIONS]

Options:
  --verbose         Show detailed analysis
  --fix             Attempt to fix issues automatically
  --module=NAME     Analyze specific module
```

**What it does:**
- Scans PHP files for database issues
- Detects hardcoded credentials
- Finds direct mysqli connections
- Recommends fixes
- Can auto-fix some issues

**Example:**
```bash
# Analyze all modules
C:\xampp\php\php.exe module_fixer.php --verbose

# Analyze specific module
C:\xampp\php\php.exe module_fixer.php --module=auth --verbose
```

---

### 5. **test_db_connections.php** (Quick Test)

**Purpose:** Quick test of all database connections

**Usage:**
```bash
C:\xampp\php\php.exe test_db_connections.php
```

**Output:**
```
Testing database connections...

Testing Students database...
  ✓ Connected to: igangaschoolofl_students_db

Testing Staff database...
  ✓ Connected to: igangaschoolofl_staffs_db

Testing Website database...
  ✓ Connected to: igangaschoolofl_website_db

Testing ICT database...
  ✓ Connected to: igangaschoolofl_ict
```

---

### 6. **health-check.php** (System Verification)

**Purpose:** Comprehensive system health verification

**Usage:**
```bash
# Run via PHP
C:\xampp\php\php.exe health-check.php

# Or open in browser
http://localhost/ISNM/health-check.php
```

**Checks:**
- ✓ PHP version (8.0+)
- ✓ Required PHP extensions
- ✓ Required files present
- ✓ Database connections
- ✓ Directory permissions

---

## Database Configuration

The system reads configuration from `.env` file in the project root.

**Location:** `c:\xampp\htdocs\ISNM\.env`

**Key Settings:**
```ini
APP_ENV=production
APP_DEBUG=false

STUDENTS_DB_HOST=localhost
STUDENTS_DB_PORT=3306
STUDENTS_DB_NAME=igangaschoolofl_students_db
STUDENTS_DB_USER=root
STUDENTS_DB_PASS=ReagaN23#

STAFF_DB_HOST=localhost
STAFF_DB_PORT=3306
STAFF_DB_NAME=igangaschoolofl_staffs_db
STAFF_DB_USER=root
STAFF_DB_PASS=ReagaN23#

WEBSITE_DB_HOST=localhost
WEBSITE_DB_PORT=3306
WEBSITE_DB_NAME=igangaschoolofl_website_db
WEBSITE_DB_USER=root
WEBSITE_DB_PASS=ReagaN23#

ICT_DB_HOST=localhost
ICT_DB_PORT=3306
ICT_DB_NAME=igangaschoolofl_ict
ICT_DB_USER=root
ICT_DB_PASS=ReagaN23#
```

**Note:** These credentials are production defaults. On local XAMPP, ensure MySQL root password is set to `ReagaN23#` or update the .env file accordingly.

---

## Troubleshooting

### Problem: "Database unavailable" on login page

**Step 1:** Check if MySQL is running
```bash
# On Windows
Get-Service | Where-Object {$_.DisplayName -like '*MySQL*' -or $_.DisplayName -like '*MariaDB*'}
```

**Step 2:** Run health check
```bash
C:\xampp\php\php.exe health-check.php
```

**Step 3:** Run master repair
```bash
C:\xampp\php\php.exe master_database_repair.php --import-sql --verbose
```

### Problem: Connection refused on specific port

**Cause:** MySQL might be on different port (3307 instead of 3306)

**Fix:** Update .env file
```ini
DB_PORT=3307
STUDENTS_DB_PORT=3307
STAFF_DB_PORT=3307
WEBSITE_DB_PORT=3307
ICT_DB_PORT=3307
```

### Problem: "Access denied for user 'root'"

**Cause:** Wrong password or no password set

**Fix 1:** Set MySQL root password
```bash
# In XAMPP Control Panel, restart MySQL
# Then set password if needed
```

**Fix 2:** Update password in .env
```ini
STUDENTS_DB_PASS=your_actual_password
STAFF_DB_PASS=your_actual_password
# etc...
```

### Problem: "Unknown database" error

**Cause:** Databases don't exist

**Fix:** Run master repair with --import-sql
```bash
C:\xampp\php\php.exe master_database_repair.php --import-sql
```

### Problem: "Table doesn't exist" for specific table

**Cause:** SQL schema wasn't fully imported

**Fix:** Run schema manager with sync
```bash
C:\xampp\php\php.exe database_schema_manager.php --sync
```

---

## Database Structure

### igangaschoolofl_students_db
- **students** - Student profiles and enrollment
- **academic_records** - Grades and performance
- **student_fee_accounts** - Tuition and billing
- **bursar_users** - Finance staff accounts
- **courses** - Course definitions

### igangaschoolofl_staffs_db
- **staff** - Staff profiles and employment
- **hr_users** - HR department accounts
- **payroll** - Salary and compensation
- **staff_activity_log** - Audit trail
- **departments** - Organizational structure

### igangaschoolofl_website_db
- **contact_submissions** - Website contact form data
- **website_announcements** - News and announcements
- **news** - News articles

### igangaschoolofl_ict
- **ict_assets** - Equipment inventory
- **ict_asset_categories** - Equipment categories
- **asset_assignments** - Equipment assignments
- **lab_computers** - Computer lab inventory

---

## Best Practices

1. **Regular Backups**
   ```bash
   # Backup all databases monthly
   mysqldump -u root -p igangaschoolofl_students_db > students_backup.sql
   mysqldump -u root -p igangaschoolofl_staffs_db > staffs_backup.sql
   mysqldump -u root -p igangaschoolofl_website_db > website_backup.sql
   mysqldump -u root -p igangaschoolofl_ict > ict_backup.sql
   ```

2. **Monitor Connection Pool**
   - Maximum 10 concurrent connections per database
   - Connections auto-close after 30 minutes of inactivity
   - Session timeout: 1 hour

3. **Error Reporting**
   - Enable APP_DEBUG=true for detailed error messages
   - Check error logs in logs/ directory
   - Review MySQL error log for connection issues

4. **Performance**
   - Clear browser cache if changes not appearing
   - Restart PHP-FPM after schema changes
   - Check database query performance in logs

---

## Support

If issues persist after running all tools:

1. **Collect Diagnostics**
   ```bash
   C:\xampp\php\php.exe health-check.php > diagnostic_report.txt
   C:\xampp\php\php.exe master_database_repair.php --verbose > repair_report.txt
   ```

2. **Check MySQL Error Log**
   - Location: `C:\xampp\mysql\data\` (look for .err file)
   - Or in XAMPP Control Panel → Logs

3. **Verify File Permissions**
   - Ensure XAMPP has read/write access to project files
   - Check if sql/ directory is readable

4. **Restart Services**
   ```bash
   # Restart XAMPP MySQL
   # Option 1: XAMPP Control Panel → MySQL → Stop, then Start
   # Option 2: Command line
   net stop MySQL57
   net start MySQL57
   ```

---

## Summary

| Tool | Purpose | Best For |
|------|---------|----------|
| master_database_repair.php | Complete recovery | Initial setup, emergency recovery |
| database_init.php | Connection testing | Quick validation |
| database_schema_manager.php | Schema validation | Missing tables, schema sync |
| module_fixer.php | Module analysis | Code quality, compatibility |
| health-check.php | System verification | Pre-flight checks |
| test_db_connections.php | Quick test | Rapid diagnostics |

---

## Emergency Recovery Steps

If system is completely down:

1. **Step 1:** Verify MySQL is running
   ```bash
   # XAMPP Control Panel or command line
   ```

2. **Step 2:** Run full repair
   ```bash
   C:\xampp\php\php.exe master_database_repair.php --import-sql --verbose
   ```

3. **Step 3:** Verify success
   ```bash
   C:\xampp\php\php.exe health-check.php
   ```

4. **Step 4:** Test login
   - Navigate to staff-login.php or student-login.php
   - Try logging in

5. **Step 5:** If still failing
   - Review error messages carefully
   - Check logs/ directory
   - Run module_fixer.php for code issues

---

**Last Updated:** 2026-07-04
**System Version:** ISNM ERP 1.0
**Database Tools Version:** 2.0
