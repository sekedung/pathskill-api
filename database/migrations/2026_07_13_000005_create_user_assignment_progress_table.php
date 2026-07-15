<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_assignment_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'submitted', 'successful'])->default('pending');
            $table->timestamps();

            $table->unique(['user_id', 'assignment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_assignment_progress');
    }
};
