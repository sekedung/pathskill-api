<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // "Full Stack Developer"
            $table->string('icon')->nullable(); // emoji atau path svg, misal "🚀"
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0); // urutan tampil 1-5
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('careers');
    }
};
