@echo off
title TechPulse IMS - Local Development Server
echo =====================================================================
echo  TECHPULSE IMS - PC HARDWARE & GAMING LOGISTICS MANAGEMENT
echo  Mini Project 3: Relational Database + Advanced PHP Native
echo  Author: Ainur Maida Syahri
echo =====================================================================
echo.
echo [1/2] Memeriksa instalasi PHP XAMPP...
if exist "C:\xampp\php\php.exe" (
    set PHP_BIN=C:\xampp\php\php.exe
) else (
    set PHP_BIN=php
)

echo [2/2] Menjalankan PHP Built-in Server pada port 8000...
echo.
echo Akses Web: http://localhost:8000
echo Akun Admin : admin / admin123
echo Akun Staff : staff / staff123
echo.
echo Tekan [Ctrl + C] untuk menghentikan server.
echo =====================================================================

"%PHP_BIN%" -S localhost:8000
pause
