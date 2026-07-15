<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_skill_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5, "Pemula" - "Pakar"
            $table->timestamps();

            // satu user hanya boleh punya 1 rating aktif per skill
            $table->unique(['user_id', 'career_skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_skill_assessments');
    }
};
