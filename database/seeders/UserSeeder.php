<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\County;
use App\Models\Constituency;
use App\Models\Ward;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('👥 Seeding users...');

        // Create test users with specific credentials
        $this->createTestUsers();

        // Create random users for each ward
        $this->createRandomUsers();

        $this->command->info('✅ Users seeded successfully!');
        $this->command->info('   Total users: ' . User::count());
    }

    /**
     * Create test users with known credentials.
     */
    private function createTestUsers(): void
    {
        $ward = Ward::first();
        $constituency = $ward?->constituency;
        $county = $constituency?->county;

        // Primary test user
        User::firstOrCreate(
            ['phone_number' => '254712345678'],
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
                'county_id' => $county?->id,
                'constituency_id' => $constituency?->id,
                'ward_id' => $ward?->id,
                'bio' => 'Community activist and local business owner.',
                'is_active' => true,
                'phone_verified_at' => now(),
            ]
        );

        // Secondary test user
        $secondWard = Ward::skip(1)->first() ?? $ward;
        $secondConstituency = $secondWard?->constituency;
        $secondCounty = $secondConstituency?->county;

        User::firstOrCreate(
            ['phone_number' => '254723456789'],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'password' => Hash::make('password123'),
                'county_id' => $secondCounty?->id,
                'constituency_id' => $secondConstituency?->id,
                'ward_id' => $secondWard?->id,
                'bio' => 'Youth leader and entrepreneur.',
                'is_active' => true,
                'phone_verified_at' => now(),
            ]
        );

        // Unverified user for testing
        User::firstOrCreate(
            ['phone_number' => '254734567890'],
            [
                'name' => 'Test Unverified',
                'email' => 'unverified@example.com',
                'password' => Hash::make('password123'),
                'county_id' => $county?->id,
                'constituency_id' => $constituency?->id,
                'ward_id' => $ward?->id,
                'bio' => null,
                'is_active' => true,
                'phone_verified_at' => null,
            ]
        );

        $this->command->info('   ✓ Created 3 test users');
        $this->command->table(
            ['phone_number', 'Name', 'Password', 'Status'],
            [
                ['254712345678', 'John Doe', 'password123', 'Verified'],
                ['254723456789', 'Jane Smith', 'password123', 'Verified'],
                ['254734567890', 'Test Unverified', 'password123', 'Unverified'],
            ]
        );
    }

    /**
     * Create random users distributed across wards.
     */
    private function createRandomUsers(): void
    {
        $wards = Ward::with('constituency.county')->get();
        
        if ($wards->isEmpty()) {
            $this->command->warn('   ⚠ No wards found. Skipping random user creation.');
            return;
        }

        $usersPerWard = 5;
        $totalCreated = 0;

        foreach ($wards as $ward) {
            for ($i = 0; $i < $usersPerWard; $i++) {
                User::factory()->create([
                    'county_id' => $ward->constituency->county->id,
                    'constituency_id' => $ward->constituency->id,
                    'ward_id' => $ward->id,
                ]);
                $totalCreated++;
            }
        }

        $this->command->info("   ✓ Created {$totalCreated} random users across all wards");
    }
}

