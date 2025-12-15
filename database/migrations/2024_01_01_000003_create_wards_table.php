<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('constituency_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->timestamps();
            
            $table->index('constituency_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wards');
    }
};

