<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_id')->constrained()->cascadeOnDelete();
            $table->string('title');                 // "Frontend Fundamentals"
            $table->text('description')->nullable(); // "Master HTML, CSS, and JavaScript basics"
            $table->unsignedInteger('order')->default(0);
            $table->unsignedInteger('total_lessons')->default(0);
            $table->unsignedInteger('total_assignments')->default(0);
            $table->boolean('ai_generated')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_modules');
    }
};
