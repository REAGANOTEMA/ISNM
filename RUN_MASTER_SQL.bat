@echo off
REM ============================================================
REM ISNM Master SQL Executor
REM Run this batch file to set up the complete system
REM ============================================================

REM MySQL connection details
set MYSQL_HOST=localhost
set MYSQL_USER=root
set MYSQL_PORT=3306
set MYSQL_DB=igangaschool_staffs

REM Check if mysql is in PATH
where mysql >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Error: mysql command not found. Make sure XAMPP is installed and mysql is in your PATH.
    echo Try adding XAMPP\mysql\bin to your system PATH environment variable.
    pause
    exit /b 1
)

echo.
echo ============================================================
echo ISNM Complete System Setup
echo ============================================================
echo.
echo This will execute all SQL files to set up:
echo - Staff database with all departments
echo - Student management system
echo - Financial dashboards
echo - HR, Security, Academic systems
echo.
echo Database: %MYSQL_DB%
echo Host: %MYSQL_HOST%
echo User: %MYSQL_USER%
echo.

pause

echo.
echo Running master SQL setup...
echo.

REM Run the master SQL file
mysql -h %MYSQL_HOST% -u %MYSQL_USER% -P %MYSQL_PORT% < "%~dp0sql\staffs\99_MASTER_ALL_DEPARTMENTS.sql"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================================
    echo SUCCESS! Database setup complete.
    echo ============================================================
    echo.
    echo All tables, views, stored procedures, and initial data
    echo have been created successfully.
    echo.
    echo You can now login with any of these credentials:
    echo.
    echo - Director General: directorgeneral@igangaschoolofnursingandmidwifery.ac.ug / DorisJoy2026
    echo - CEO: ceo@igangaschoolofnursingandmidwifery.ac.ug / Lovely2God
    echo - Secretary: secretary@igangaschoolofnursingandmidwifery.ac.ug / Lovely2God
    echo - Director ICT: dannybict@igangaschoolofnursingandmidwifery.ac.ug / Lovely2God
    echo.
    echo For complete credential list, see:
    echo sql\staffs\99_MASTER_ALL_DEPARTMENTS.sql (lines 40-60)
    echo.
) else (
    echo.
    echo ============================================================
    echo ERROR: Database setup failed!
    echo ============================================================
    echo.
    echo Check the error messages above for details.
    echo Common issues:
    echo - MySQL server is not running
    echo - Incorrect username/password
    echo - Database already exists (you may need to drop first)
    echo.
)

pause
