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
        Schema::table('deployment_logs', function (Blueprint $table) {
            // Add indexes for better query performance
            $table->index('deployment_id');
            $table->index('log_level');
            $table->index('created_at');
            
            // Composite index for common queries
            $table->index(['deployment_id', 'log_level']);
            $table->index(['deployment_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deployment_logs', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['deployment_id']);
            $table->dropIndex(['log_level']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['deployment_id', 'log_level']);
            $table->dropIndex(['deployment_id', 'created_at']);
        });
    }
};