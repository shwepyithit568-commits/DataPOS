@echo off
REM ============================================================
REM  DataPOS — Auto-fix "public/hot" leftover
REM  Run this after starting XAMPP / rebooting the computer.
REM
REM  If Vite dev server (npm run dev) is NOT running but
REM  public/hot exists, CSS/JS break. This script removes it.
REM ============================================================

cd /d "D:\xmapp\htdocs\data_ecommerce"

if exist "public\hot" (
    del "public\hot"
    echo [OK] public\hot removed — CSS/JS will use compiled build assets.
) else (
    echo [OK] public\hot not found — no fix needed.
)

timeout /t 3 >nul
