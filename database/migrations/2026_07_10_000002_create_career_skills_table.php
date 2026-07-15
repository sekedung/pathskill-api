<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_id')->constrained()->cascadeOnDelete();
            $table->string('skill_name');                     // "Docker"
            $table->enum('category', ['core', 'tools', 'soft_skills'])->default('core');
            $table->decimal('industry_requirement', 3, 1)->default(4.0); // misal 4.4
            $table->unsignedInteger('order')->default(0);      // urutan tampil dalam career
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_skills');
    }
};
