<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CleanupDeploymentLogs::class,
        Commands\ProcessScheduledDeployments::class,
        Commands\CleanupStaleQueueJobs::class,
        Commands\TestEmailNotification::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Clean up deployment logs older than 30 days daily
        $schedule->command('deployment:cleanup-logs --days=30')
                 ->daily()
                 ->description('Clean up old deployment logs');
                 
        // Process scheduled deployments every minute
        $schedule->command('deployments:process-scheduled')
                 ->everyMinute()
                 ->description('Process scheduled deployments');
                 
        // Clean up stale queue jobs every hour
        $schedule->command('deployments:cleanup-stale-jobs --hours=2')
                 ->hourly()
                 ->description('Clean up stale queue jobs');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}