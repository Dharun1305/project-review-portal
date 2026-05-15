@echo off
:: ============================================================
::  setup.bat  —  College Project Portal : Database Setup
::  Fixed: window stays open on ALL errors for diagnosis
:: ============================================================

color 0A
title Project Portal — Database Setup

set MYSQL_BIN=C:\xampp\mysql\bin\mysql.exe
set SCHEMA=%~dp0schema.sql

echo.
echo  ===================================================
echo   College Project Submission ^& Review Portal
echo   DATABASE SETUP
echo  ===================================================
echo.

:: ── Check XAMPP mysql.exe exists ────────────────────────────
echo  [1/3] Checking MySQL binary...
if not exist "%MYSQL_BIN%" (
    echo.
    echo  [ERROR] mysql.exe not found at:
    echo          %MYSQL_BIN%
    echo.
    echo  Make sure XAMPP is installed at C:\xampp
    echo  and that MySQL/MariaDB is included.
    echo.
    goto :END
)
echo  [OK] mysql.exe found.

:: ── Check schema.sql exists ─────────────────────────────────
if not exist "%SCHEMA%" (
    echo.
    echo  [ERROR] schema.sql not found at:
    echo          %SCHEMA%
    echo.
    echo  Make sure schema.sql is in the same folder as setup.bat.
    echo.
    goto :END
)

:: ── Check MySQL is running ──────────────────────────────────
echo  [2/3] Connecting to MySQL...
"%MYSQL_BIN%" -u root -e "SELECT 'connected';" >nul 2>&1
if errorlevel 1 (
    echo.
    echo  [ERROR] Cannot connect to MySQL.
    echo.
    echo  Please:
    echo    1. Open XAMPP Control Panel
    echo    2. Click START next to MySQL
    echo    3. Wait for the green indicator
    echo    4. Run this setup.bat again
    echo.
    goto :END
)
echo  [OK] Connected to MySQL.

:: ── Import schema ───────────────────────────────────────────
echo  [3/3] Importing schema.sql...
"%MYSQL_BIN%" -u root < "%SCHEMA%"
if errorlevel 1 (
    echo.
    echo  [ERROR] Failed to import schema.sql.
    echo          This may mean MySQL is not running or
    echo          the schema.sql file has a syntax error.
    echo.
    goto :END
)
echo  [OK] Import successful.

:: ── Verify ──────────────────────────────────────────────────
echo.
echo  Tables created in "project_portal":
"%MYSQL_BIN%" -u root -D project_portal -e "SHOW TABLES;"

echo.
echo  ===================================================
echo   SETUP COMPLETE!
echo.
echo   Default Admin:
echo     Email    : admin@portal.com
echo     Password : password
echo     Role     : Admin
echo  ===================================================
echo.
echo  Now run:  run.bat  to launch the application.

:END
echo.
echo  Press any key to close this window.
pause >nul
