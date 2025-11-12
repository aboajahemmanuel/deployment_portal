<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduler registrations (Laravel 12 routing-based scheduling)
// Clean up deployment logs older than 30 days daily
Schedule::command('deployment:cleanup-logs --days=30')
    ->daily()
    ->description('Clean up old deployment logs');

// Process scheduled deployments every minute
Schedule::command('deployments:process-scheduled')
    ->everyMinute()
    ->description('Process scheduled deployments');

// Clean up stale queue jobs every hour
Schedule::command('deployments:cleanup-stale-jobs --hours=2')
    ->hourly()
    ->description('Clean up stale queue jobs');
