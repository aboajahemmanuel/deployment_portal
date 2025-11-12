<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Services\UserService;
use App\Models\Project;
use App\Models\ScheduledDeployment;
use App\Models\SecurityPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ScheduledDeploymentPolicy;
use App\Policies\SecurityPolicyPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(UserService::class, function ($app) {
            return new UserService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model policies
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(ScheduledDeployment::class, ScheduledDeploymentPolicy::class);
        Gate::policy(SecurityPolicy::class, SecurityPolicyPolicy::class);
    }
}