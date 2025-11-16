# Windows Deployment Setup Guide

This guide explains how to set up job processing for the Deployment Management system on Windows servers.

## Current Development Setup

In development, you use:
```bash
composer run dev
```

This command uses `concurrently` to run multiple processes:
- Web server (`php artisan serve`)
- Queue worker (`php artisan queue:work --tries=1`)
- Scheduler (`php artisan schedule:work`)
- Frontend asset builder (`npm run dev`)

## Windows Production Options

### Option 1: Windows Task Scheduler (Recommended)

#### Queue Worker Setup:

1. Create the batch file `start-queue-worker.bat`:
   ```batch
   @echo off
   cd /d "C:\xampp\htdocs\deployment-management"
   php artisan queue:work --tries=3 --sleep=3 --max-jobs=100
   ```

2. Configure Windows Task Scheduler:
   - Open Task Scheduler
   - Create Basic Task
   - Name: "Deployment Queue Worker"
   - Trigger: "When the computer starts"
   - Action: Start a program
   - Program/script: `C:\xampp\htdocs\deployment-management\start-queue-worker.bat`
   - Check "Run whether user is logged on or not"

#### Scheduler Setup:

1. Create the batch file `start-scheduler.bat`:
   ```batch
   @echo off
   cd /d "C:\xampp\htdocs\deployment-management"
   php artisan schedule:run
   ```

2. Configure Windows Task Scheduler:
   - Create Basic Task
   - Name: "Deployment Scheduler"
   - Trigger: Daily, repeating every 1 minute
   - Action: Start a program
   - Program/script: `C:\xampp\htdocs\deployment-management\start-scheduler.bat`

### Option 2: Windows Services with NSSM

1. Download NSSM from https://nssm.cc/download
2. Install as a service:
   ```cmd
   nssm install DeploymentQueueWorker "C:\xampp\php\php.exe" "artisan" "queue:work --tries=3 --sleep=3"
   nssm install DeploymentScheduler "C:\xampp\php\php.exe" "artisan" "schedule:run"
   ```

### Option 3: Manual Batch Execution

For testing or temporary setups, use the `start-services.bat` script:
```batch
@echo off
echo Starting Deployment Management Services...

cd /d "C:\xampp\htdocs\deployment-management"

echo Starting Queue Worker...
start "Queue Worker" /min php artisan queue:work --tries=3 --sleep=3

echo Starting Scheduler...
start "Scheduler" /min php artisan schedule:work

echo Services started. Press Ctrl+C to stop.
pause
```

### Option 4: Production-Ready Batch Script

For production environments, use the robust `production-queue-worker.bat` script:
```batch
@echo off
setlocal

cd /d "C:\xampp\htdocs\deployment-management"

:loop
echo [%date% %time%] Starting queue worker...
php artisan queue:work --tries=3 --sleep=3 --max-jobs=500 --max-time=3600
echo [%date% %time%] Queue worker stopped. Restarting in 5 seconds...
timeout /t 5 /nobreak >nul
goto loop
```

## Monitoring and Maintenance

1. Check Windows Event Viewer for any errors
2. Monitor the Laravel logs in `storage/logs/`
3. Use the built-in dashboard to monitor job statuses
4. Regularly check that services are running as expected

## Troubleshooting

1. If jobs aren't processing:
   - Verify the queue worker is running
   - Check `storage/logs/laravel.log` for errors
   - Confirm QUEUE_CONNECTION in `.env` is set correctly

2. If scheduled deployments aren't triggering:
   - Verify the scheduler is running
   - Check that cron expressions are correct
   - Ensure the `deployments:process-scheduled` command is working

3. Common Windows-specific issues:
   - Path issues with XAMPP installation
   - Permission issues with writing to log files
   - PHP CLI memory limits