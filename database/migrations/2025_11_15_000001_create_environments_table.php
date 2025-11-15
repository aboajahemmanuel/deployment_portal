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
        Schema::create('environments', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'Development', 'Staging', 'Production'
            $table->string('slug')->unique(); // e.g., 'development', 'staging', 'production'
            $table->string('server_base_path'); // e.g., 'C:\xampp\htdocs\dev', 'C:\xampp\htdocs\staging'
            $table->string('server_unc_path'); // e.g., '\\10.10.15.59\c$\xampp\htdocs\dev'
            $table->string('web_base_url'); // e.g., 'http://dev.fmdqgroup.com', 'http://staging.fmdqgroup.com'
            $table->string('deploy_endpoint_base'); // e.g., 'http://101-php-01.fmdqgroup.com/dep_env_dev'
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0); // For sorting environments
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('environments');
    }
};
