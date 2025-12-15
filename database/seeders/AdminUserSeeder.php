<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Seeding admin users...');

        // Create default super admin
        AdminUser::firstOrCreate(
            ['email' => 'admin@party.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Create default moderator
        AdminUser::firstOrCreate(
            ['email' => 'moderator@party.com'],
            [
                'name' => 'Content Moderator',
                'password' => Hash::make('moderator123'),
                'role' => 'moderator',
                'is_active' => true,
            ]
        );

        // Create additional moderators
        AdminUser::firstOrCreate(
            ['email' => 'mod.siaya@party.com'],
            [
                'name' => 'Siaya County Moderator',
                'password' => Hash::make('moderator123'),
                'role' => 'moderator',
                'is_active' => true,
            ]
        );

        AdminUser::firstOrCreate(
            ['email' => 'mod.nakuru@party.com'],
            [
                'name' => 'Nakuru County Moderator',
                'password' => Hash::make('moderator123'),
                'role' => 'moderator',
                'is_active' => true,
            ]
        );

        $this->command->info('   ✓ Created ' . AdminUser::count() . ' admin users');
        $this->command->newLine();
        $this->command->warn('⚠️  Please change the default passwords in production!');
        $this->command->newLine();
        $this->command->table(
            ['Email', 'Role', 'Password'],
            [
                ['admin@party.com', 'admin', 'admin123'],
                ['moderator@party.com', 'moderator', 'moderator123'],
                ['mod.siaya@party.com', 'moderator', 'moderator123'],
                ['mod.nakuru@party.com', 'moderator', 'moderator123'],
            ]
        );
    }
}
