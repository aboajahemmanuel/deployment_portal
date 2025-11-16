@echo off
echo Starting Deployment Management Services...
echo.

cd /d "C:\xampp\htdocs\deployment-management"

echo Starting Queue Worker...
start "Queue Worker" /min php artisan queue:work --tries=3 --sleep=3

echo Starting Scheduler...
start "Scheduler" /min php artisan schedule:work

echo Services started. Press Ctrl+C to stop.
pause