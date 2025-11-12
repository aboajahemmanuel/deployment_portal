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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
            $table->string('timezone')->nullable()->after('last_login_at');
            $table->boolean('email_notifications')->default(true)->after('timezone');
            $table->boolean('system_alerts')->default(true)->after('email_notifications');
            $table->boolean('security_alerts')->default(true)->after('system_alerts');
            $table->boolean('marketing_emails')->default(false)->after('security_alerts');
            $table->timestamp('author_promoted_at')->nullable()->after('marketing_emails');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_login_at',
                'timezone',
                'email_notifications',
                'system_alerts',
                'security_alerts',
                'marketing_emails',
                'author_promoted_at'
            ]);
        });
    }
};
