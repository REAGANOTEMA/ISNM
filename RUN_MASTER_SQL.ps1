# ============================================================
# ISNM Master SQL Executor (PowerShell)
# Run this script to set up the complete system
# ============================================================

# Configuration
$MYSQL_HOST = "localhost"
$MYSQL_USER = "root"
$MYSQL_PORT = 3306
$MYSQL_DB = "igangaschool_staffs"
$XAMPP_PATH = "C:\xampp"
$MYSQL_PATH = "$XAMPP_PATH\mysql\bin\mysql.exe"
$SCRIPT_DIR = Split-Path -Parent $MyInvocation.MyCommand.Path
$SQL_FILE = Join-Path $SCRIPT_DIR "sql\staffs\99_MASTER_ALL_DEPARTMENTS.sql"

Write-Host "============================================================"
Write-Host "ISNM Complete System Setup"
Write-Host "============================================================"
Write-Host ""

# Check if MySQL exists
if (-not (Test-Path $MYSQL_PATH)) {
    Write-Host "Error: mysql.exe not found at $MYSQL_PATH"
    Write-Host "Make sure XAMPP is installed at: $XAMPP_PATH"
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "This will execute all SQL files to set up:"
Write-Host "- Staff database with all departments"
Write-Host "- Student management system"
Write-Host "- Financial dashboards"
Write-Host "- HR, Security, Academic systems"
Write-Host ""
Write-Host "Database: $MYSQL_DB"
Write-Host "Host: $MYSQL_HOST"
Write-Host "User: $MYSQL_USER"
Write-Host ""
Write-Host "SQL File: $SQL_FILE"
Write-Host ""
Read-Host "Press Enter to continue or Ctrl+C to cancel"

Write-Host ""
Write-Host "Running master SQL setup..."
Write-Host ""

# Run the master SQL file
try {
    $output = & $MYSQL_PATH -h $MYSQL_HOST -u $MYSQL_USER -P $MYSQL_PORT | Get-Content $SQL_FILE
    
    Write-Host ""
    Write-Host "============================================================"
    Write-Host "SUCCESS! Database setup complete."
    Write-Host "============================================================"
    Write-Host ""
    Write-Host "All tables, views, stored procedures, and initial data"
    Write-Host "have been created successfully."
    Write-Host ""
    Write-Host "You can now login with any of these credentials:"
    Write-Host ""
    Write-Host "- Director General: directorgeneral@igangaschoolofnursingandmidwifery.ac.ug / DorisJoy2026"
    Write-Host "- CEO: ceo@igangaschoolofnursingandmidwifery.ac.ug / Lovely2God"
    Write-Host "- Secretary: secretary@igangaschoolofnursingandmidwifery.ac.ug / Lovely2God"
    Write-Host "- Director ICT: dannybict@igangaschoolofnursingandmidwifery.ac.ug / Lovely2God"
    Write-Host ""
    Write-Host "For complete credential list, see:"
    Write-Host "sql\staffs\99_MASTER_ALL_DEPARTMENTS.sql (lines 40-60)"
    Write-Host ""
} catch {
    Write-Host ""
    Write-Host "============================================================"
    Write-Host "ERROR: Database setup failed!"
    Write-Host "============================================================"
    Write-Host ""
    Write-Host "Error: $_"
    Write-Host ""
    Write-Host "Common issues:"
    Write-Host "- MySQL server is not running"
    Write-Host "- Incorrect username/password"
    Write-Host "- Database already exists (you may need to drop first)"
    Write-Host ""
}

Read-Host "Press Enter to exit"
