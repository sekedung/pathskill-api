<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('education_background')->nullable()->after('email');
            $table->text('interest')->nullable()->after('education_background');
            $table->foreignId('career_goal_id')
                ->nullable()
                ->after('interest')
                ->constrained('careers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['career_goal_id']);
            $table->dropColumn(['education_background', 'interest', 'career_goal_id']);
        });
    }
};
