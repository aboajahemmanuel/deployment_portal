@echo off
cd /d "C:\xampp\htdocs\deployment-management"
php artisan queue:work --tries=3 --sleep=3 --max-jobs=100