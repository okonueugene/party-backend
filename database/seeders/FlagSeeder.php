<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Flag;
use App\Models\Post;
use App\Models\Comment;
use App\Models\User;
use App\Models\AdminUser;

class FlagSeeder extends Seeder
{
    /**
     * Flag reasons.
     */
    private array $reasons = [
        'spam' => 'This content appears to be spam or promotional material.',
        'harassment' => 'This content contains harassment or bullying.',
        'hate_speech' => 'This content contains hate speech or discrimination.',
        'violence' => 'This content promotes or depicts violence.',
        'misinformation' => 'This content contains false or misleading information.',
        'inappropriate_content' => 'This content is inappropriate for this platform.',
        'copyright_violation' => 'This content violates copyright laws.',
        'other' => 'Other reason not listed above.',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚩 Seeding flags...');

        $posts = Post::all();
        $comments = Comment::all();
        $users = User::whereNotNull('phone_verified_at')->get();
        $admins = AdminUser::all();

        if ($users->isEmpty()) {
            $this->command->warn('   ⚠ No verified users found. Skipping flag creation.');
            return;
        }

        $postFlagsCreated = 0;
        $commentFlagsCreated = 0;

        // Create flags for some posts (10% of posts get flagged)
        foreach ($posts as $post) {
            if (rand(1, 10) > 1) {
                continue;
            }

            // 1-3 users flag the post
            $flagCount = rand(1, 3);
            $flaggers = $users->where('id', '!=', $post->user_id)->random(min($flagCount, $users->count() - 1));
            
            foreach ($flaggers as $user) {
                $reason = array_rand($this->reasons);
                $status = $this->getRandomStatus();
                
                $flag = Flag::create([
                    'user_id' => $user->id,
                    'flaggable_id' => $post->id,
                    'flaggable_type' => Post::class,
                    'reason' => $reason,
                    'description' => rand(1, 10) <= 7 ? $this->reasons[$reason] : null,
                    'status' => $status,
                    'reviewed_by' => $status !== 'pending' && $admins->isNotEmpty() ? $admins->random()->id : null,
                    'reviewed_at' => $status !== 'pending' ? now()->subDays(rand(0, 7)) : null,
                    'created_at' => $post->created_at->addHours(rand(1, 48)),
                ]);
                
                $postFlagsCreated++;
            }

            // Update post flag count and status
            $flagsCount = $post->flags()->count();
            $post->update([
                'flags_count' => $flagsCount,
                'is_flagged' => $flagsCount >= 3,
            ]);
        }

        // Create flags for some comments (5% of comments get flagged)
        foreach ($comments as $comment) {
            if (rand(1, 20) > 1) {
                continue;
            }

            $reason = array_rand($this->reasons);
            $status = $this->getRandomStatus();
            $flagger = $users->where('id', '!=', $comment->user_id)->random();
            
            Flag::create([
                'user_id' => $flagger->id,
                'flaggable_id' => $comment->id,
                'flaggable_type' => Comment::class,
                'reason' => $reason,
                'description' => rand(1, 10) <= 5 ? $this->reasons[$reason] : null,
                'status' => $status,
                'reviewed_by' => $status !== 'pending' && $admins->isNotEmpty() ? $admins->random()->id : null,
                'reviewed_at' => $status !== 'pending' ? now()->subDays(rand(0, 7)) : null,
                'created_at' => $comment->created_at->addHours(rand(1, 24)),
            ]);
            
            $commentFlagsCreated++;
        }

        $this->command->info("   ✓ Created {$postFlagsCreated} post flags");
        $this->command->info("   ✓ Created {$commentFlagsCreated} comment flags");
        $this->command->info('✅ Flags seeded successfully!');
    }

    /**
     * Get a random flag status with weighted probability.
     */
    private function getRandomStatus(): string
    {
        $rand = rand(1, 10);
        
        if ($rand <= 4) {
            return 'pending';
        } elseif ($rand <= 7) {
            return 'resolved';
        } elseif ($rand <= 9) {
            return 'dismissed';
        } else {
            return 'escalated';
        }
    }
}

