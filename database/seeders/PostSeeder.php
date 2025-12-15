<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PostSeeder extends Seeder
{
    /**
     * Sample post contents for realistic data.
     */
    private array $sampleContents = [
        "Our community water project is making great progress! 💧 We've connected 50 more households this month. Thank you to everyone who contributed.",
        "Reminder: Town hall meeting tomorrow at 3 PM at the community center. We'll discuss the new road construction project. Your voice matters!",
        "Congratulations to our local football team for winning the county championship! 🏆 You've made us all proud!",
        "The youth empowerment program is now accepting applications. Young people aged 18-35 can apply for skills training and startup funding.",
        "Road safety alert: The main highway has some dangerous potholes near the market area. Please drive carefully until repairs are done.",
        "Great news! The new health center will open next month. This will significantly improve healthcare access for our community.",
        "Calling all farmers: Agricultural extension officers will be visiting next week. Bring your questions about modern farming techniques.",
        "Thank you to everyone who participated in the community cleanup exercise yesterday. Our streets have never looked better!",
        "Local market day reminder: Every Saturday from 6 AM. Support local farmers and artisans by buying locally.",
        "Education update: The bursary application deadline is this Friday. Don't miss out on this opportunity for your children's education.",
        "Security advisory: There have been reports of suspicious activities. Please report anything unusual to the local chief's office.",
        "Cultural event: Traditional dance competition this Sunday at the stadium. All age groups are welcome to participate!",
        "Business opportunity: A new factory is opening and will create 200 jobs. Applications start next Monday at the county offices.",
        "Weather alert: Heavy rains expected this week. Farmers are advised to harvest mature crops and secure their livestock.",
        "Community health outreach: Free medical screening at the dispensary this weekend. Early detection saves lives!",
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📝 Seeding posts...');

        $users = User::whereNotNull('phone_verified_at')->get();

        if ($users->isEmpty()) {
            $this->command->warn('   ⚠ No verified users found. Skipping post creation.');
            return;
        }

        $postsCreated = 0;

        foreach ($users as $user) {
            // Create 2-5 posts per user
            $postCount = rand(2, 5);
            
            for ($i = 0; $i < $postCount; $i++) {
                $content = $this->sampleContents[array_rand($this->sampleContents)];
                
                Post::create([
                    'user_id' => $user->id,
                    'content' => $content,
                    'image' => rand(1, 10) <= 3 ? 'posts/sample-' . rand(1, 10) . '.jpg' : null,
                    'audio' => rand(1, 10) <= 1 ? 'posts/audio-' . rand(1, 5) . '.mp3' : null,
                    'county_id' => $user->county_id,
                    'constituency_id' => $user->constituency_id,
                    'ward_id' => $user->ward_id,
                    'likes_count' => 0,
                    'comments_count' => 0,
                    'shares_count' => 0,
                    'flags_count' => 0,
                    'is_flagged' => false,
                    'is_active' => true,
                    'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                ]);
                
                $postsCreated++;
            }
        }

        $this->command->info("   ✓ Created {$postsCreated} posts");
        $this->command->info('✅ Posts seeded successfully!');
    }
}

