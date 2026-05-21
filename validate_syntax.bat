@echo off
cd /d "c:\xampp\htdocs\ISNM.worktrees\agents-organogram-department-navigation"

echo Validating PHP Syntax...
echo.

"C:\xampp\php\php.exe" -l "dashboards\requirements-director.php"
echo.

"C:\xampp\php\php.exe" -l "includes\requirements_functions.php"
echo.

"C:\xampp\php\php.exe" -l "setup_requirements_portal.php"
echo.

"C:\xampp\php\php.exe" -l "verify_requirements_portal.php"
echo.

"C:\xampp\php\php.exe" -l "auth-handler.php"
echo.

echo Syntax validation complete!
pause
