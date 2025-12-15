<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Like;
use App\Models\Post;
use App\Models\Comment;
use App\Models\User;

class LikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('❤️  Seeding likes...');

        $posts = Post::all();
        $comments = Comment::all();
        $users = User::whereNotNull('phone_verified_at')->get();

        if ($users->isEmpty()) {
            $this->command->warn('   ⚠ No verified users found. Skipping like creation.');
            return;
        }

        $postLikesCreated = 0;
        $commentLikesCreated = 0;

        // Create likes for posts
        foreach ($posts as $post) {
            // Random number of users will like each post (0-60% of users)
            $likeCount = rand(0, (int) ($users->count() * 0.6));
            $likers = $users->random(min($likeCount, $users->count()));
            
            foreach ($likers as $user) {
                // Check if user already liked this post
                $exists = Like::where('user_id', $user->id)
                    ->where('likeable_id', $post->id)
                    ->where('likeable_type', Post::class)
                    ->exists();
                
                if (!$exists) {
                    Like::create([
                        'user_id' => $user->id,
                        'likeable_id' => $post->id,
                        'likeable_type' => Post::class,
                        'created_at' => $post->created_at->addMinutes(rand(1, 2880)),
                    ]);
                    $postLikesCreated++;
                }
            }

            // Update post likes count
            $post->update(['likes_count' => $post->likes()->count()]);
        }

        // Create likes for comments
        foreach ($comments as $comment) {
            // Random number of users will like each comment (0-30% of users)
            $likeCount = rand(0, (int) ($users->count() * 0.3));
            
            if ($likeCount > 0) {
                $likers = $users->random(min($likeCount, $users->count()));
                
                foreach ($likers as $user) {
                    // Check if user already liked this comment
                    $exists = Like::where('user_id', $user->id)
                        ->where('likeable_id', $comment->id)
                        ->where('likeable_type', Comment::class)
                        ->exists();
                    
                    if (!$exists) {
                        Like::create([
                            'user_id' => $user->id,
                            'likeable_id' => $comment->id,
                            'likeable_type' => Comment::class,
                            'created_at' => $comment->created_at->addMinutes(rand(1, 1440)),
                        ]);
                        $commentLikesCreated++;
                    }
                }

                // Update comment likes count
                $comment->update(['likes_count' => $comment->likes()->count()]);
            }
        }

        $this->command->info("   ✓ Created {$postLikesCreated} post likes");
        $this->command->info("   ✓ Created {$commentLikesCreated} comment likes");
        $this->command->info('✅ Likes seeded successfully!');
    }
}

