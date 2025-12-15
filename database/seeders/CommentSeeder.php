<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

class CommentSeeder extends Seeder
{
    /**
     * Sample comment contents for realistic data.
     */
    private array $sampleComments = [
        "This is exactly what our community needs!",
        "Thank you for sharing this important information.",
        "I fully support this initiative.",
        "When will this be implemented?",
        "Great work! Keep it up.",
        "Can you provide more details?",
        "I have a question about this...",
        "This is wonderful news!",
        "We need more of this in our area.",
        "Finally! We've been waiting for this.",
        "How can we get involved?",
        "Sharing this with my neighbors.",
        "Very informative, thank you!",
        "This will benefit many people.",
        "Looking forward to seeing the results.",
        "Is there a deadline for this?",
        "Well said!",
        "I agree completely.",
        "This deserves more attention.",
        "Let's all participate!",
    ];

    /**
     * Sample reply contents.
     */
    private array $sampleReplies = [
        "I agree with you!",
        "Good point.",
        "Thanks for clarifying.",
        "That's a valid concern.",
        "Let me add to that...",
        "Exactly what I was thinking.",
        "You're right about this.",
        "I have a different perspective on this.",
        "Could you elaborate?",
        "That's helpful, thanks!",
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('💬 Seeding comments...');

        $posts = Post::all();
        $users = User::whereNotNull('phone_verified_at')->get();

        if ($posts->isEmpty() || $users->isEmpty()) {
            $this->command->warn('   ⚠ No posts or users found. Skipping comment creation.');
            return;
        }

        $commentsCreated = 0;
        $repliesCreated = 0;

        foreach ($posts as $post) {
            // Create 0-8 comments per post
            $commentCount = rand(0, 8);
            $postComments = [];
            
            for ($i = 0; $i < $commentCount; $i++) {
                $user = $users->random();
                $content = $this->sampleComments[array_rand($this->sampleComments)];
                
                $comment = Comment::create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                    'content' => $content,
                    'parent_id' => null,
                    'likes_count' => 0,
                    'is_active' => true,
                    'created_at' => $post->created_at->addMinutes(rand(5, 1440)),
                ]);
                
                $postComments[] = $comment;
                $commentsCreated++;
            }

            // Create replies to some comments
            foreach ($postComments as $comment) {
                if (rand(1, 10) <= 3) { // 30% chance of having replies
                    $replyCount = rand(1, 3);
                    
                    for ($j = 0; $j < $replyCount; $j++) {
                        $user = $users->random();
                        $content = $this->sampleReplies[array_rand($this->sampleReplies)];
                        
                        Comment::create([
                            'post_id' => $post->id,
                            'user_id' => $user->id,
                            'content' => $content,
                            'parent_id' => $comment->id,
                            'likes_count' => 0,
                            'is_active' => true,
                            'created_at' => $comment->created_at->addMinutes(rand(5, 120)),
                        ]);
                        
                        $repliesCreated++;
                    }
                }
            }

            // Update post comment count
            $post->update(['comments_count' => $post->allComments()->count()]);
        }

        $this->command->info("   ✓ Created {$commentsCreated} comments");
        $this->command->info("   ✓ Created {$repliesCreated} replies");
        $this->command->info('✅ Comments seeded successfully!');
    }
}

