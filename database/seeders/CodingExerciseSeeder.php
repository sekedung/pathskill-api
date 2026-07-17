<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\CodingExercise;
use Illuminate\Database\Seeder;

class CodingExerciseSeeder extends Seeder
{
    /**
     * Isi coding exercise contoh untuk "Assignment 1: Personal Portfolio Page",
     * lanjutan dari Quiz (HTML Semantik & Layout Responsif). Jalankan setelah
     * LearningPathSeeder & QuizSeeder (assignment-nya harus sudah ada).
     */
    public function run(): void
    {
        $assignment = Assignment::where('title', 'Assignment 1: Personal Portfolio Page')->first();

        if (! $assignment) {
            return; // jalankan LearningPathSeeder dulu
        }

        CodingExercise::updateOrCreate(
            ['assignment_id' => $assignment->id],
            [
                'title' => 'Latihan: Section Hero Responsif',
                'description' => 'Lengkapi section hero di bawah supaya menggunakan elemen semantik yang benar dan layout-nya tetap rapi di layar kecil maupun besar. Ini latihan singkat sebelum kamu mengerjakan Mini Project portofolio secara utuh.',
                'learning_objectives' => [
                    'Menerapkan elemen HTML semantik (<header>, <nav>, <main>) pada struktur halaman',
                    'Membuat layout yang menyesuaikan lebar layar tanpa media query tambahan',
                ],
                'requirements' => [
                    'Bungkus navigasi utama dengan <nav>, bukan <div>',
                    'Gunakan CSS Grid/Flexbox agar kolom otomatis menyesuaikan lebar layar',
                    'Tambahkan atribut alt pada setiap <img>',
                ],
                'language' => 'html',
                'starter_code' => <<<'CODE'
<!-- Lengkapi section hero di bawah ini -->
<div class="hero">
  <div class="hero-nav">
    <a href="#about">About</a>
    <a href="#projects">Projects</a>
    <a href="#contact">Contact</a>
  </div>
  <div class="hero-content">
    <img src="profile.jpg">
    <h1>Halo, saya [Nama Kamu]</h1>
    <p>Frontend Developer</p>
  </div>
</div>
CODE,
                'hint' => 'Ganti <div class="hero-nav"> dengan <nav>, dan bungkus keseluruhan section dengan <header>. Untuk layout, coba grid-template-columns: repeat(auto-fit, minmax(...)) seperti di materi Quiz sebelumnya.',
            ]
        );
    }
}
