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
        Schema::create('project_environments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('environment_id')->constrained()->onDelete('cascade');
            $table->string('deploy_endpoint'); // Full URL to deployment script for this environment
            $table->string('rollback_endpoint'); // Full URL to rollback script for this environment
            $table->string('application_url'); // Full URL where the app is accessible in this environment
            $table->string('project_path'); // Full path on server where project is deployed
            $table->text('env_variables')->nullable(); // Environment-specific .env variables
            $table->string('branch')->default('main'); // Environment-specific branch
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Ensure a project can only have one configuration per environment
            $table->unique(['project_id', 'environment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_environments');
    }
};
