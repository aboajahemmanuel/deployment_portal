# Scheduled Deployments Setup

This document explains how to set up scheduled deployments in your Laravel application.

## Overview

The deployment scheduling feature allows you to schedule deployments to run automatically at specific times. You can also set up recurring deployments for regular maintenance.

## Components

1. **ScheduledDeployment Model**: Stores information about scheduled deployments
2. **ScheduledDeploymentController**: Handles CRUD operations for scheduled deployments
3. **ProcessScheduledDeployments Command**: Dispatches scheduled deployments to the queue
4. **ProcessScheduledDeployment Job**: Processes individual scheduled deployments in the queue
5. **Kernel Schedule**: Runs the processing command every minute

## Setting up Cron Job (Windows)

Since this is a Windows environment, you'll need to set up a scheduled task to run the Laravel scheduler:

### Method 1: Using Windows Task Scheduler

1. Open Task Scheduler
2. Click "Create Basic Task"
3. Name the task "Laravel Scheduler"
4. Set the trigger to "Daily" and repeat every 1 minute
5. Set the action to run a program:
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\deployment-management\artisan schedule:run`
   - Start in: `C:\xampp\htdocs\deployment-management`

### Method 2: Using a Batch File

Create a batch file `run_scheduler.bat`:

```batch
@echo off
cd /d C:\xampp\htdocs\deployment-management
C:\xampp\php\php.exe artisan schedule:run
```

Then set up a scheduled task to run this batch file every minute.

## Queue Worker Setup

Scheduled deployments are processed through Laravel queues for better performance and reliability.

### Starting the Queue Worker

Run the queue worker to process scheduled deployments:

```bash
php artisan queue:work
```

### Process Management

For production environments, use a process supervisor like Supervisor (Linux) or Windows service to keep the queue worker running.

## Available Commands

### Process Scheduled Deployments
```bash
php artisan deployments:process-scheduled
```
Manually process scheduled deployments that are due (dispatches to queue).

### Test Scheduled Deployment
```bash
# Create a scheduled deployment for 5 minutes in the future
php artisan test:scheduled-deployment

# Create a scheduled deployment that is due now
php artisan test:scheduled-deployment --due
```

## Routes

- `GET /scheduled-deployments` - List all scheduled deployments
- `GET /scheduled-deployments/create` - Create a new scheduled deployment
- `POST /scheduled-deployments` - Store a new scheduled deployment
- `GET /scheduled-deployments/{scheduledDeployment}` - Show a scheduled deployment
- `GET /scheduled-deployments/{scheduledDeployment}/edit` - Edit a scheduled deployment
- `PUT /scheduled-deployments/{scheduledDeployment}` - Update a scheduled deployment
- `DELETE /scheduled-deployments/{scheduledDeployment}` - Delete a scheduled deployment
- `POST /scheduled-deployments/{scheduledDeployment}/cancel` - Cancel a scheduled deployment

## Features

1. **One-time Scheduling**: Schedule a deployment to run at a specific time
2. **Recurring Scheduling**: Set up deployments to run daily, weekly, or monthly
3. **Cancellation**: Cancel scheduled deployments before they run
4. **Status Tracking**: Track the status of scheduled deployments (pending, processing, completed, failed, cancelled)
5. **User Association**: Track which user created each scheduled deployment
6. **Queue-based Processing**: Deployments are processed through Laravel queues for better performance
7. **Timezone Awareness**: The application uses the system timezone to ensure accurate scheduling

## Database Schema

The `scheduled_deployments` table includes the following columns:

- `project_id`: The project to deploy
- `user_id`: The user who scheduled the deployment
- `scheduled_at`: When the deployment should run
- `status`: Current status of the scheduled deployment
- `description`: Optional description
- `is_recurring`: Whether this is a recurring deployment
- `recurrence_pattern`: Pattern for recurring deployments (daily, weekly, monthly)
- `last_run_at`: When the deployment was last run
- `next_run_at`: When the next run should occur (for recurring deployments)

## Security

Scheduled deployments use the same authorization policies as regular deployments. Users can only schedule deployments for projects they have permission to deploy.

## Timezone Handling

The application automatically uses the system timezone for scheduling to ensure consistency between the application and system time. You can override this by setting the `APP_TIMEZONE` environment variable in your `.env` file.