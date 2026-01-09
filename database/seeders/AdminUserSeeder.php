<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\AdminRole; // Make sure this is imported
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('👤 Creating admin users with roles...');

        // Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@party.com'],
            [
                'phone_number' => '254700000000',
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole(AdminRole::SUPER_ADMIN);

        // Regular Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@party.com'],
            [
                'phone_number' => '254700000001',
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole(AdminRole::ADMIN);

        // Moderator
        $moderator = User::firstOrCreate(
            ['email' => 'moderator@party.com'],
            [
                'phone_number' => '254700000002',
                'name' => 'Moderator User',
                'password' => Hash::make('moderator123'),
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ]
        );
        $moderator->assignRole(AdminRole::MODERATOR);

        // Content Manager
        $contentManager = User::firstOrCreate(
            ['email' => 'content@party.com'],
            [
                'phone_number' => '254700000003',
                'name' => 'Content Manager',
                'password' => Hash::make('content123'),
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ]
        );
        $contentManager->assignRole(AdminRole::CONTENT_MANAGER);

        // Analyst
        $analyst = User::firstOrCreate(
            ['email' => 'analyst@party.com'],
            [
                'phone_number' => '254700000004',
                'name' => 'Analyst User',
                'password' => Hash::make('analyst123'),
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ]
        );
        $analyst->assignRole(AdminRole::ANALYST);

        $this->command->info('✅ Admin users with roles created!');
        $this->command->table(
            ['Email', 'Password', 'Role', 'Permissions Count'],
            [
                ['superadmin@party.com', 'admin123', 'Super Admin', count(AdminRole::SUPER_ADMIN->defaultPermissions())],
                ['admin@party.com', 'admin123', 'Admin', count(AdminRole::ADMIN->defaultPermissions())],
                ['moderator@party.com', 'moderator123', 'Moderator', count(AdminRole::MODERATOR->defaultPermissions())],
                ['content@party.com', 'content123', 'Content Manager', count(AdminRole::CONTENT_MANAGER->defaultPermissions())],
                ['analyst@party.com', 'analyst123', 'Analyst', count(AdminRole::ANALYST->defaultPermissions())],
            ]
        );
    }
}