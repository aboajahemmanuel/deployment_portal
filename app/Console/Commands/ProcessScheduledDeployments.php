<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledDeployment;
use App\Jobs\ProcessScheduledDeployment;
use Illuminate\Support\Facades\Log;

class ProcessScheduledDeployments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deployments:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled deployments that are due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing scheduled deployments...');
        
        // Get all pending scheduled deployments that are due
        $scheduledDeployments = ScheduledDeployment::due()->get();
        
        if ($scheduledDeployments->isEmpty()) {
            $this->info('No scheduled deployments are due at this time.');
            return;
        }
        
        $this->info("Dispatching {$scheduledDeployments->count()} scheduled deployments to queue...");
        
        foreach ($scheduledDeployments as $scheduledDeployment) {
            try {
                // Dispatch the job to the queue
                ProcessScheduledDeployment::dispatch($scheduledDeployment);

                // Mark as queued; actual queue ID isn't available here
                $scheduledDeployment->update([
                    'status' => 'queued',
                ]);

                $this->info("Dispatched scheduled deployment #{$scheduledDeployment->id} to queue");

                Log::info("Dispatched scheduled deployment to queue", [
                    'scheduled_deployment_id' => $scheduledDeployment->id,
                    'project_id' => $scheduledDeployment->project_id,
                ]);
            } catch (\Exception $e) {
                $this->error("Failed to dispatch scheduled deployment #{$scheduledDeployment->id}: " . $e->getMessage());
                
                // Mark as failed
                $scheduledDeployment->update(['status' => 'failed']);
                
                Log::error("Failed to dispatch scheduled deployment #{$scheduledDeployment->id}", [
                    'exception' => $e,
                    'scheduled_deployment' => $scheduledDeployment
                ]);
            }
        }
        
        $this->info('Finished dispatching scheduled deployments to queue.');
    }
}