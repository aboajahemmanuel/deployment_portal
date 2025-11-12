<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('security_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Vulnerability thresholds
            $table->integer('max_critical_vulnerabilities')->default(0);
            $table->integer('max_high_vulnerabilities')->default(0);
            $table->integer('max_medium_vulnerabilities')->default(10);
            $table->integer('max_low_vulnerabilities')->default(50);
            
            // Scan configuration
            $table->json('required_scan_types')->default('["sast", "dependency", "secrets"]');
            $table->boolean('block_on_secrets')->default(true);
            $table->boolean('block_on_license_violations')->default(false);
            $table->json('allowed_licenses')->nullable();
            $table->json('blocked_licenses')->nullable();
            
            // Timeout and retry settings
            $table->integer('scan_timeout_minutes')->default(30);
            $table->integer('max_retry_attempts')->default(3);
            
            // Environment-specific settings
            $table->json('environment_overrides')->nullable();
            
            // Notification settings
            $table->boolean('notify_on_failure')->default(true);
            $table->boolean('notify_on_new_vulnerabilities')->default(true);
            $table->json('notification_channels')->default('["email"]');
            
            $table->timestamps();
            
            $table->unique(['project_id', 'name'], 'security_policies_project_name_unique');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_policies');
    }
};
