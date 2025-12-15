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
            $table->text('content')->nullable();
            $table->string('image')->nullable();
            $table->string('audio')->nullable();
            $table->foreignId('county_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('constituency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->unsignedInteger('flags_count')->default(0);
            $table->boolean('is_flagged')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['county_id', 'created_at']);
            $table->index(['constituency_id', 'created_at']);
            $table->index(['ward_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('is_flagged');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
