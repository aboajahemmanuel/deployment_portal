<?php

namespace App\Services\Contracts;

use App\Models\Deployment;
use App\Models\PipelineStage;
use App\Models\Project;

interface PipelineServiceInterface
{
    /**
     * Create pipeline stages for a deployment.
     */
    public function createPipeline(Deployment $deployment, ?string $template = 'default'): void;

    /**
     * Execute the next pipeline stage.
     */
    public function executeNextStage(Deployment $deployment): ?PipelineStage;

    /**
     * Complete a stage and advance pipeline.
     */
    public function completeStage(PipelineStage $stage, bool $success = true, ?string $output = null, ?string $errorMessage = null): ?PipelineStage;

    /**
     * Get pipeline templates.
     */
    public function getPipelineTemplates(): array;

    /**
     * Get pipeline analytics for a project.
     */
    public function getProjectAnalytics(Project $project): array;

    /**
     * Get system-wide pipeline metrics.
     */
    public function getSystemMetrics(): array;

    /**
     * Simulate pipeline execution.
     */
    public function simulateExecution(Deployment $deployment): void;

    /**
     * Cancel pipeline execution.
     */
    public function cancelPipeline(Deployment $deployment): bool;
}
