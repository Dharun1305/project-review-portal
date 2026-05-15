@echo off
:: ============================================================
::  run.bat  —  College Project Portal : Start & Launch
::  Fixed: window stays open on ALL errors for diagnosis
:: ============================================================

color 0B
title Project Portal — Starting...

set XAMPP=C:\xampp
set MYSQL_BIN=%XAMPP%\mysql\bin\mysql.exe
set APACHE_BIN=%XAMPP%\apache\bin\httpd.exe
set PORTAL_URL=http://localhost/project_portal/

echo.
echo  ===================================================
echo   College Project Submission ^& Review Portal
echo   LAUNCHING APPLICATION
echo  ===================================================
echo.

:: ── Step 1: Check XAMPP exists ──────────────────────────────
echo  [1/4] Checking XAMPP installation...
if not exist "%XAMPP%" (
    echo.
    echo  [ERROR] XAMPP folder not found at: C:\xampp
    echo.
    echo  Please do ONE of the following:
    echo    A) Install XAMPP from https://www.apachefriends.org
    echo    B) If installed elsewhere, edit this file and change:
    echo       set XAMPP=C:\xampp
    echo       to the correct path.
    echo.
    goto :END
)
echo  [OK] XAMPP found at %XAMPP%

:: ── Step 2: Start Apache ────────────────────────────────────
echo  [2/4] Starting Apache...
tasklist /fi "imagename eq httpd.exe" 2>nul | find /i "httpd.exe" >nul
if not errorlevel 1 (
    echo  [OK] Apache is already running.
) else (
    net start Apache2.4 >nul 2>&1
    timeout /t 2 /nobreak >nul
    tasklist /fi "imagename eq httpd.exe" 2>nul | find /i "httpd.exe" >nul
    if errorlevel 1 (
        echo.
        echo  [!] Apache could not be auto-started.
        echo      Please open XAMPP Control Panel and click START next to Apache.
        echo      Then press any key here to continue...
        pause >nul
    ) else (
        echo  [OK] Apache started.
    )
)

:: ── Step 3: Start MySQL ─────────────────────────────────────
echo  [3/4] Starting MySQL...
tasklist /fi "imagename eq mysqld.exe" 2>nul | find /i "mysqld.exe" >nul
if not errorlevel 1 (
    echo  [OK] MySQL is already running.
) else (
    net start MySQL >nul 2>&1
    timeout /t 3 /nobreak >nul
    tasklist /fi "imagename eq mysqld.exe" 2>nul | find /i "mysqld.exe" >nul
    if errorlevel 1 (
        echo.
        echo  [!] MySQL could not be auto-started.
        echo      Please open XAMPP Control Panel and click START next to MySQL.
        echo      Then press any key here to continue...
        pause >nul
    ) else (
        echo  [OK] MySQL started.
    )
)

:: ── Step 4: Check/Create database ──────────────────────────
echo  [4/4] Checking database...
if not exist "%MYSQL_BIN%" (
    echo  [!] mysql.exe not found — skipping DB check.
    goto :OPEN
)
"%MYSQL_BIN%" -u root -e "USE project_portal;" >nul 2>&1
if errorlevel 1 (
    echo  [!] Database "project_portal" not found.
    echo      Running setup.bat to create it now...
    echo.
    call "%~dp0setup.bat"
) else (
    echo  [OK] Database "project_portal" is ready.
)

:: ── Open browser ────────────────────────────────────────────
:OPEN
echo.
echo  ===================================================
echo   Opening: %PORTAL_URL%
echo  ===================================================
start "" "%PORTAL_URL%"

:END
echo.
echo  Press any key to close this window.
pause >nul
