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
            // Role and status fields
            $table->boolean('is_admin')->default(false)->after('password');
            $table->boolean('is_active')->default(true)->after('is_admin');
            
            // Security and audit fields
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('last_login_at');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            
            // Add indexes for performance on Linux/MySQL
            $table->index(['is_admin', 'is_active'], 'users_admin_active_index');
            $table->index('email_verified_at', 'users_email_verified_index');
            $table->index('locked_until', 'users_locked_until_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('users_admin_active_index');
            $table->dropIndex('users_email_verified_index');
            $table->dropIndex('users_locked_until_index');
            
            // Drop columns
            $table->dropColumn([
                'is_admin',
                'is_active',
                'last_login_at',
                'failed_login_attempts',
                'locked_until'
            ]);
        });
    }
};