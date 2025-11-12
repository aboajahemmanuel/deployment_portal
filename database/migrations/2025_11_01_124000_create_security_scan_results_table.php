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
        Schema::create('security_scan_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deployment_id')->constrained()->onDelete('cascade');
            $table->foreignId('pipeline_stage_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('scan_type', ['sast', 'dependency', 'secrets', 'infrastructure', 'container']);
            $table->string('tool_name', 100);
            $table->enum('severity', ['critical', 'high', 'medium', 'low', 'info']);
            $table->string('vulnerability_id')->nullable();
            $table->string('cve_id')->nullable();
            $table->string('title', 500);
            $table->text('description');
            $table->string('file_path', 1000)->nullable();
            $table->integer('line_number')->nullable();
            $table->text('code_snippet')->nullable();
            $table->text('remediation_advice')->nullable();
            $table->string('reference_url')->nullable();
            $table->enum('status', ['open', 'acknowledged', 'false_positive', 'fixed', 'ignored'])->default('open');
            $table->json('metadata')->nullable(); // Additional tool-specific data
            $table->timestamp('first_detected_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->foreign('acknowledged_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('acknowledgment_reason')->nullable();
            $table->timestamps();
            
            $table->index(['deployment_id', 'scan_type']);
            $table->index(['deployment_id', 'severity']);
            $table->index(['status', 'severity']);
            $table->index(['vulnerability_id', 'cve_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_scan_results');
    }
};