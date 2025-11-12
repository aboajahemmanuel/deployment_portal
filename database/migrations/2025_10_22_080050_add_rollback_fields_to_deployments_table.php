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
        Schema::table('deployments', function (Blueprint $table) {
            $table->boolean('is_rollback')->default(false)->after('commit_hash');
            $table->foreignId('rollback_target_id')->nullable()->constrained('deployments')->after('is_rollback');
            $table->text('rollback_reason')->nullable()->after('rollback_target_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->dropColumn(['is_rollback', 'rollback_target_id', 'rollback_reason']);
        });
    }
};