<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Share;
use App\Models\Post;
use App\Models\User;

class ShareSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔄 Seeding shares...');

        $posts = Post::all();
        $users = User::whereNotNull('phone_verified_at')->get();

        if ($posts->isEmpty() || $users->isEmpty()) {
            $this->command->warn('   ⚠ No posts or users found. Skipping share creation.');
            return;
        }

        $sharesCreated = 0;

        foreach ($posts as $post) {
            // Only some posts get shared (40% chance)
            if (rand(1, 10) > 4) {
                continue;
            }

            // Random number of users will share each post (1-20% of users)
            $shareCount = rand(1, max(1, (int) ($users->count() * 0.2)));
            $sharers = $users->random(min($shareCount, $users->count()));
            
            foreach ($sharers as $user) {
                // Users typically don't share their own posts
                if ($user->id === $post->user_id) {
                    continue;
                }

                // Check if user already shared this post
                $exists = Share::where('user_id', $user->id)
                    ->where('post_id', $post->id)
                    ->exists();
                
                if (!$exists) {
                    Share::create([
                        'user_id' => $user->id,
                        'post_id' => $post->id,
                        'created_at' => $post->created_at->addMinutes(rand(30, 4320)),
                    ]);
                    $sharesCreated++;
                }
            }

            // Update post shares count
            $post->update(['shares_count' => $post->shares()->count()]);
        }

        $this->command->info("   ✓ Created {$sharesCreated} shares");
        $this->command->info('✅ Shares seeded successfully!');
    }
}

