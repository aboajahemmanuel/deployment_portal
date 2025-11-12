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
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deployment_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., 'preparation', 'build', 'test', 'deploy', 'verify'
            $table->string('display_name'); // e.g., 'Code Preparation', 'Build & Compile'
            $table->text('description')->nullable();
            $table->integer('order')->default(0); // Stage order in pipeline
            $table->enum('status', ['pending', 'running', 'success', 'failed', 'skipped'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration')->nullable(); // Duration in seconds
            $table->text('output')->nullable(); // Stage output/logs
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional stage-specific data
            $table->timestamps();
            
            $table->index(['deployment_id', 'order']);
            $table->index(['deployment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
    }
};
