<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Contracts\DeploymentServiceInterface;
use App\Services\Contracts\ProjectServiceInterface;
use App\Services\Contracts\NotificationServiceInterface;
use App\Services\Contracts\SecurityServiceInterface;
use App\Services\Contracts\PipelineServiceInterface;
use App\Services\DeploymentService;
use App\Services\ProjectService;
use App\Services\NotificationService;
use App\Services\SecurityScannerService;
use App\Services\PipelineService;

class DeploymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind service interfaces to their implementations
        $this->app->bind(DeploymentServiceInterface::class, DeploymentService::class);
        $this->app->bind(ProjectServiceInterface::class, ProjectService::class);
        $this->app->bind(NotificationServiceInterface::class, NotificationService::class);
        $this->app->bind(SecurityServiceInterface::class, SecurityScannerService::class);
        $this->app->bind(PipelineServiceInterface::class, PipelineService::class);

        // Register singletons for services that should be shared
        $this->app->singleton('deployment.service', function ($app) {
            return $app->make(DeploymentServiceInterface::class);
        });

        $this->app->singleton('project.service', function ($app) {
            return $app->make(ProjectServiceInterface::class);
        });

        $this->app->singleton('notification.service', function ($app) {
            return $app->make(NotificationServiceInterface::class);
        });

        $this->app->singleton('security.service', function ($app) {
            return $app->make(SecurityServiceInterface::class);
        });

        $this->app->singleton('pipeline.service', function ($app) {
            return $app->make(PipelineServiceInterface::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            DeploymentServiceInterface::class,
            ProjectServiceInterface::class,
            NotificationServiceInterface::class,
            SecurityServiceInterface::class,
            PipelineServiceInterface::class,
            'deployment.service',
            'project.service',
            'notification.service',
            'security.service',
            'pipeline.service',
        ];
    }
}
