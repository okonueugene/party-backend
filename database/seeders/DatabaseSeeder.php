<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════╗');
        $this->command->info('║           🌱 PARTY APP DATABASE SEEDER 🌱            ║');
        $this->command->info('╚══════════════════════════════════════════════════════╝');
        $this->command->info('');

        $this->call([
            // 1. Geographic data (counties, constituencies, wards)
            GeographicDataSeeder::class,
            
            // 2. Admin users (administrators and moderators)
            AdminUserSeeder::class,
            
            // 3. Regular users (distributed across wards)
            UserSeeder::class,
            
            // 4. Posts (created by users)
            PostSeeder::class,
            
            // 5. Comments (on posts with nested replies)
            CommentSeeder::class,
            
            // 6. Likes (on posts and comments)
            LikeSeeder::class,
            
            // 7. Shares (of posts)
            ShareSeeder::class,
            
            // 8. Flags (reports on posts and comments)
            FlagSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════╗');
        $this->command->info('║         ✅ DATABASE SEEDING COMPLETE! ✅             ║');
        $this->command->info('╚══════════════════════════════════════════════════════╝');
        $this->command->info('');
        
        $this->displaySummary();
    }

    /**
     * Display seeding summary with record counts.
     */
    private function displaySummary(): void
    {
        $this->command->info('📊 SEEDING SUMMARY:');
        $this->command->info('');
        
        $this->command->table(
            ['Model', 'Count'],
            [
                ['Counties', \App\Models\County::count()],
                ['Constituencies', \App\Models\Constituency::count()],
                ['Wards', \App\Models\Ward::count()],
                ['Admin Users', \App\Models\AdminUser::count()],
                ['Users', \App\Models\User::count()],
                ['Posts', \App\Models\Post::count()],
                ['Comments', \App\Models\Comment::count()],
                ['Likes', \App\Models\Like::count()],
                ['Shares', \App\Models\Share::count()],
                ['Flags', \App\Models\Flag::count()],
            ]
        );
        
        $this->command->info('');
    }
}
