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
             // Add admin role column
            $table->string('admin_role')->nullable()->after('is_admin');
            
            // Add permissions column (JSON array)
            $table->json('permissions')->nullable()->after('admin_role');
            
            // Add last login tracking
            $table->timestamp('last_login_at')->nullable()->after('phone_verified_at');
            
            // Add index for admin queries
            $table->index(['is_admin', 'admin_role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->dropColumn(['admin_role', 'permissions', 'last_login_at']);
            $table->dropIndex(['is_admin', 'admin_role']);
        });
    }
};
