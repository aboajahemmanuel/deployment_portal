<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\DeploymentAdminController;
use App\Http\Controllers\Author\AuthorController;
use App\Http\Controllers\Author\BookController;
use App\Http\Controllers\Author\WalletController;
use App\Http\Controllers\Author\PayoutController;
use App\Http\Controllers\Author\AuthorProfileController;
use App\Http\Controllers\User\BookSubmissionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\BookReviewController;
use App\Http\Controllers\Admin\PayoutManagementController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DeploymentController;
use App\Http\Controllers\ScheduledDeploymentController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\SecurityController;


Route::get('/', function () {
    return view('auth.login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/menu', function () { return view('menu.index'); })->name('menu.index');
    
    // Notifications
    // Route::get('/notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread'); // DISABLED
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/toggle-dark-mode', [NotificationController::class, 'toggleDarkMode'])->name('toggle-dark-mode');
    
    // Admin routes for deployment manager
    Route::middleware('role:admin')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            // Deployment Manager Dashboard
            Route::get('/deployment-dashboard', [DeploymentAdminController::class, 'dashboard'])->name('deployment-dashboard');    
            // Project Management
            Route::get('/projects', [DeploymentAdminController::class, 'projects'])->name('projects');
            
            // Deployment File Generator
            Route::get('/deployment-files', [\App\Http\Controllers\Admin\DeploymentFileController::class, 'index'])->name('deployment-files.index');
            Route::get('/deployment-files/create', [\App\Http\Controllers\Admin\DeploymentFileController::class, 'create'])->name('deployment-files.create');
            Route::post('/deployment-files', [\App\Http\Controllers\Admin\DeploymentFileController::class, 'store'])->name('deployment-files.store');
            Route::get('/deployment-files/edit', [\App\Http\Controllers\Admin\DeploymentFileController::class, 'edit'])->name('deployment-files.edit');
            Route::post('/deployment-files/update', [\App\Http\Controllers\Admin\DeploymentFileController::class, 'update'])->name('deployment-files.update');
            Route::delete('/deployment-files', [\App\Http\Controllers\Admin\DeploymentFileController::class, 'destroy'])->name('deployment-files.destroy');
            
            // Environment Management
            Route::prefix('environments')->name('environments.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\EnvironmentController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Admin\EnvironmentController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Admin\EnvironmentController::class, 'store'])->name('store');
                Route::get('/{environment}', [\App\Http\Controllers\Admin\EnvironmentController::class, 'show'])->name('show');
                Route::get('/{environment}/edit', [\App\Http\Controllers\Admin\EnvironmentController::class, 'edit'])->name('edit');
                Route::put('/{environment}', [\App\Http\Controllers\Admin\EnvironmentController::class, 'update'])->name('update');
                Route::delete('/{environment}', [\App\Http\Controllers\Admin\EnvironmentController::class, 'destroy'])->name('destroy');
                Route::patch('/{environment}/toggle-active', [\App\Http\Controllers\Admin\EnvironmentController::class, 'toggleActive'])->name('toggle-active');
            });
            
            // Deployment Management
            Route::get('/deployments', [DeploymentAdminController::class, 'deployments'])->name('deployments');
            
            // User Management - Enhanced
            Route::prefix('users')->name('users.')->group(function () {
                Route::get('/', [UserManagementController::class, 'index'])->name('index');
                Route::get('/create', [UserManagementController::class, 'create'])->name('create');
                Route::post('/', [UserManagementController::class, 'store'])->name('store');
                Route::get('/{user}', [UserManagementController::class, 'show'])->name('show');
                Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
                Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
                Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
                Route::post('/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('reset-password');
                Route::post('/{user}/send-verification', [UserManagementController::class, 'sendVerificationEmail'])->name('send-verification');
                Route::post('/{user}/promote-author', [UserManagementController::class, 'promoteToAuthor'])->name('promote-author');
                Route::post('/{user}/login-as', [UserManagementController::class, 'loginAsUser'])->name('login-as');
                Route::get('/trashed/list', [UserManagementController::class, 'trashed'])->name('trashed');
                Route::post('/{id}/restore', [UserManagementController::class, 'restore'])->name('restore');
            });
        });
    });
    
    // Deployment Manager Routes
    Route::middleware(['role:admin|developer'])->group(function () {
        // Move scheduled deployments outside the deployments prefix to avoid conflicts
        Route::prefix('scheduled-deployments')->name('scheduled-deployments.')->group(function () {
            Route::get('/', [ScheduledDeploymentController::class, 'index'])->name('index');
            Route::get('/create', [ScheduledDeploymentController::class, 'create'])->name('create');
            Route::post('/', [ScheduledDeploymentController::class, 'store'])->name('store');
            Route::get('/{scheduledDeployment}', [ScheduledDeploymentController::class, 'show'])->name('show');
            Route::get('/{scheduledDeployment}/edit', [ScheduledDeploymentController::class, 'edit'])->name('edit');
            Route::put('/{scheduledDeployment}', [ScheduledDeploymentController::class, 'update'])->name('update');
            Route::delete('/{scheduledDeployment}', [ScheduledDeploymentController::class, 'destroy'])->name('destroy');
            Route::patch('/{scheduledDeployment}/cancel', [ScheduledDeploymentController::class, 'cancel'])->name('cancel');
        });
            Route::get('/monitoring', [DeploymentController::class, 'monitoring'])->name('monitoring');
            Route::get('/realtime-monitoring', [DeploymentController::class, 'realtimeMonitoring'])->name('realtime-monitoring');

        Route::prefix('deployments')->name('deployments.')->group(function () {
            Route::get('/', [DeploymentController::class, 'index'])->name('index');
            Route::get('/create', [DeploymentController::class, 'create'])->name('create');
            Route::post('/', [DeploymentController::class, 'store'])->name('store');
            Route::get('/{project}', [DeploymentController::class, 'show'])->name('show');
            Route::get('/{project}/edit', [DeploymentController::class, 'edit'])->name('edit');
            Route::put('/{project}', [DeploymentController::class, 'update'])->name('update');
            Route::delete('/{project}', [DeploymentController::class, 'destroy'])->name('destroy');
            Route::post('/{project}/deploy', [DeploymentController::class, 'deploy'])->name('deploy');
            Route::post('/{project}/rollback/{targetDeployment}', [DeploymentController::class, 'rollback'])->name('rollback');
            Route::get('/{project}/deployments/{deployment}/logs', [DeploymentController::class, 'logs'])->name('logs');
            Route::get('/detailed-logs/{project}/{deployment}', [DeploymentController::class, 'detailedLogs'])->name('detailed-logs');
            Route::get('/{project}/commits', [DeploymentController::class, 'commits'])->name('commits');
           // Route::get('/monitoring', [DeploymentController::class, 'monitoring'])->name('monitoring');
        });

        // Pipeline Visualization Routes
        Route::prefix('pipelines')->name('pipelines.')->group(function () {
            Route::get('/deployment/{deployment}', [PipelineController::class, 'show'])->name('show');
            Route::get('/project/{project}', [PipelineController::class, 'project'])->name('project');
            Route::get('/templates', [PipelineController::class, 'templates'])->name('templates');
            Route::get('/analytics', [PipelineController::class, 'analytics'])->name('analytics');
            Route::get('/{deployment}/status', [PipelineController::class, 'status'])->name('status');
            Route::get('/stages/{stage}/details', [PipelineController::class, 'stageDetails'])->name('stage.details');
            Route::post('/{deployment}/simulate', [PipelineController::class, 'simulate'])->name('simulate');
            Route::post('/{deployment}/advance', [PipelineController::class, 'advance'])->name('advance');
            Route::get('/deployments/{deployment}/realtime-logs', [DeploymentController::class, 'realtimeLogs'])->name('realtime-logs');
        });

        // Security routes
        Route::get('/security/dashboard', [SecurityController::class, 'dashboard'])->name('security.dashboard');
        Route::get('/security/policies', [SecurityController::class, 'policies'])->name('security.policies');
        Route::get('/security/policies/create', [SecurityController::class, 'createPolicy'])->name('security.policies.create');
        Route::post('/security/policies', [SecurityController::class, 'storePolicy'])->name('security.policies.store');
        Route::get('/security/policies/{policy}/edit', [SecurityController::class, 'editPolicy'])->name('security.policies.edit');
        Route::put('/security/policies/{policy}', [SecurityController::class, 'updatePolicy'])->name('security.policies.update');
        Route::get('/security/policies/{policy}', [SecurityController::class, 'showPolicy'])->name('security.policies.show');
        Route::post('/security/policies/{policy}/duplicate', [SecurityController::class, 'duplicatePolicy'])->name('security.policies.duplicate');
        Route::delete('/security/policies/{policy}', [SecurityController::class, 'deletePolicy'])->name('security.policies.delete');
        Route::get('/security/deployment/{deployment}/results', [SecurityController::class, 'deploymentResults'])->name('security.deployment.results');
        Route::post('/security/deployment/{deployment}/scan', [SecurityController::class, 'triggerScan'])->name('security.deployment.scan');
        Route::get('/security/vulnerability/{result}/details', [SecurityController::class, 'getVulnerabilityDetails'])->name('security.vulnerability.details');
        Route::post('/security/vulnerability/{result}/acknowledge', [SecurityController::class, 'acknowledgeVulnerability'])->name('security.vulnerability.acknowledge');
        Route::post('/security/vulnerability/{result}/false-positive', [SecurityController::class, 'markFalsePositive'])->name('security.vulnerability.false-positive');

        // Admin-only security routes
        Route::middleware('role:admin')->prefix('security')->name('security.')->group(function () {
            Route::get('/policies/create', [SecurityController::class, 'createPolicy'])->name('policies.create');
            Route::post('/policies', [SecurityController::class, 'storePolicy'])->name('policies.store');
        });
    });
    
    // User Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Notification routes
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
});

require __DIR__.'/auth.php';