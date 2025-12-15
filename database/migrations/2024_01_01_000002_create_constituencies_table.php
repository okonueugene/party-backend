<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('constituencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            
            $table->index('county_id');
            $table->unique(['county_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('constituencies');
    }
};
