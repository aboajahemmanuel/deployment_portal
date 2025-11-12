<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledDeployment;
use Illuminate\Support\Facades\Log;

class CleanupStaleQueueJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deployments:cleanup-stale-jobs {--hours=2 : Hours after which to consider jobs stale}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up stale queue jobs for scheduled deployments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->option('hours');
        $this->info("Cleaning up stale queue jobs older than {$hours} hours...");
        
        // Find scheduled deployments that have been queued for too long
        $staleDeployments = ScheduledDeployment::where('status', 'queued')
            ->whereNotNull('queue_job_id')
            ->where('updated_at', '<', now()->subHours($hours))
            ->get();
        
        if ($staleDeployments->isEmpty()) {
            $this->info('No stale queue jobs found.');
            return;
        }
        
        $this->info("Found {$staleDeployments->count()} stale queue jobs to clean up.");
        
        foreach ($staleDeployments as $deployment) {
            try {
                // Reset the deployment to pending status
                $deployment->update([
                    'status' => 'pending',
                    'queue_job_id' => null
                ]);
                
                $this->info("Reset scheduled deployment #{$deployment->id} from stale queue state");
                
                Log::info("Cleaned up stale queue job for scheduled deployment #{$deployment->id}", [
                    'scheduled_deployment_id' => $deployment->id,
                    'previous_queue_job_id' => $deployment->queue_job_id,
                    'hours_stale' => $hours
                ]);
            } catch (\Exception $e) {
                $this->error("Failed to clean up scheduled deployment #{$deployment->id}: " . $e->getMessage());
                Log::error("Failed to clean up stale queue job for scheduled deployment #{$deployment->id}", [
                    'exception' => $e,
                    'scheduled_deployment' => $deployment
                ]);
            }
        }
        
        $this->info('Finished cleaning up stale queue jobs.');
    }
}
