@echo off
chcp 65001 >nul
title ISNM Database Fix - Removes #1813 Tablespace Error
color 0c
echo.
echo ============================================================
echo   ISNM DATABASE FIX
echo   This will STOP MySQL, delete old database files,
echo   and START MySQL again so you can run the SQL setup.
echo ============================================================
echo.

echo [1/4] Stopping MySQL...
net stop MySQL80 >nul 2>&1
timeout /t 3 /nobreak >nul
echo      MySQL stopped.
echo.

echo [2/4] Deleting old database folder...
set "DBDIR=C:\xampp\mysql\data\igangaschoolofl_staffs_db"

if exist "%DBDIR%" (
    echo      Found folder: %DBDIR%
    echo      Deleting all files...
    
    REM Delete all files recursively
    del /f /s /q "%DBDIR%\*.*" >nul 2>&1
    
    REM Delete all subdirectories
    for /d %%p in ("%DBDIR%\*") do @rd /s /q "%%p" >nul 2>&1
    
    REM Delete the main folder
    rd /s /q "%DBDIR%" >nul 2>&1
    
    if exist "%DBDIR%" (
        echo      [ERROR] Could not delete folder!
        echo      Please manually delete: %DBDIR%
        echo      Then run this script again.
        pause
        exit /b 1
    ) else (
        echo      [OK] Folder deleted successfully.
    )
) else (
    echo      No old folder found - already clean.
)
echo.

echo [3/4] Starting MySQL...
net start MySQL80 >nul 2>&1
timeout /t 3 /nobreak >nul
echo      MySQL started.
echo.

echo [4/4] Done!
echo.
echo ============================================================
echo   NOW DO THIS:
echo   1. Open your browser
echo   2. Go to: http://localhost/ISNM/force_setup.php
echo   3. Click "FIX AND SETUP EVERYTHING"
echo   4. Then login at: http://localhost/ISNM/staff-login.php
echo ============================================================
echo.
pause
