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
        Schema::table('scheduled_deployments', function (Blueprint $table) {
            $table->foreignId('environment_id')->nullable()->after('project_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheduled_deployments', function (Blueprint $table) {
            $table->dropForeign(['environment_id']);
            $table->dropColumn('environment_id');
        });
    }
};
