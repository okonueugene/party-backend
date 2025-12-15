<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ward_id')->constrained()->cascadeOnDelete();
            $table->text('content')->nullable();
            $table->json('images')->nullable(); // Array of image paths
            $table->string('audio_path')->nullable();
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->boolean('is_flagged')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['ward_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('is_flagged');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};

