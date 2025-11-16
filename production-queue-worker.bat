@echo off
setlocal

cd /d "C:\xampp\htdocs\deployment-management"

:loop
echo [%date% %time%] Starting queue worker...
php artisan queue:work --tries=3 --sleep=3 --max-jobs=500 --max-time=3600
echo [%date% %time%] Queue worker stopped. Restarting in 5 seconds...
timeout /t 5 /nobreak >nul
goto loop