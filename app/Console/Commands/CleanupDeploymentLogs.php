<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeploymentLog;
use Carbon\Carbon;

class CleanupDeploymentLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deployment:cleanup-logs {--days=30 : Number of days to keep logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old deployment logs to prevent database growth';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Cleaning up deployment logs older than {$days} days (before {$cutoffDate->toDateTimeString()})");
        
        $deletedCount = DeploymentLog::where('created_at', '<', $cutoffDate)->delete();
        
        $this->info("Deleted {$deletedCount} old deployment log entries.");
        
        return Command::SUCCESS;
    }
}