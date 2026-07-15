<?php

namespace App\Console\Commands;

use App\Models\Career;
use App\Services\GroqService;
use Illuminate\Console\Command;
use RuntimeException;

class TestGroqConnection extends Command
{
    /**
     * php artisan groq:test
     * php artisan groq:test --career="DevOps Engineer"
     */
    protected $signature = 'groq:test {--career= : Nama career yang mau dites, default DevOps Engineer}';

    protected $description = 'Tes koneksi ke Groq API secara terisolasi, tanpa perlu login/register/skill assessment dulu.';

    public function handle(GroqService $groq): int
    {
        $careerName = $this->option('career') ?? 'DevOps Engineer';

        $this->info("Mencari career '{$careerName}'...");
        $career = Career::where('name', $careerName)->first();

        if (! $career) {
            $this->error("Career '{$careerName}' tidak ditemukan. Pastikan sudah jalankan `php artisan db:seed`.");
            $this->line('Career yang tersedia: ' . Career::pluck('name')->implode(', '));
            return self::FAILURE;
        }

        // dummy skill gap, biar nggak perlu ada user/assessment beneran dulu
        $dummySkillGaps = $career->skills->take(3)->map(fn ($skill) => [
            'skill_name' => $skill->skill_name,
            'current' => 1, // pura-pura user masih pemula di semua skill
            'required' => $skill->industry_requirement,
        ])->toArray();

        if (empty($dummySkillGaps)) {
            $this->error("Career '{$careerName}' belum punya skill sama sekali. Jalankan CareerSeeder dulu.");
            return self::FAILURE;
        }

        $this->info('Skill gap yang dikirim ke Groq:');
        $this->table(
            ['Skill', 'Current', 'Required'],
            collect($dummySkillGaps)->map(fn ($g) => [$g['skill_name'], $g['current'], $g['required']])
        );

        $this->info('Memanggil Groq API... (bisa makan waktu beberapa detik)');
        $start = microtime(true);

        try {
            $result = $groq->generateLearningPath($career, $dummySkillGaps);
        } catch (RuntimeException $e) {
            $this->error('❌ Gagal: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Checklist kalau gagal:');
            $this->line('1. GROQ_API_KEY sudah diset di .env?');
            $this->line('2. Model masih aktif? Cek https://console.groq.com/docs/deprecations');
            $this->line('3. Koneksi internet server jalan normal?');
            return self::FAILURE;
        }

        $elapsed = round(microtime(true) - $start, 2);

        $this->info("✅ Berhasil! ({$elapsed}s)");
        $this->newLine();
        $this->info('Modul yang di-generate Groq:');

        foreach ($result['modules'] as $i => $module) {
            $this->line(($i + 1) . ". {$module['title']}");
            $this->line('   ' . ($module['description'] ?? '-'));
            $this->line('   Lessons: ' . count($module['lessons']) . ' | Assignments: ' . count($module['assignments']));
        }

        $this->newLine();
        $this->comment('Catatan: command ini TIDAK menyimpan hasil ke database (hanya tes koneksi + parsing). Buat generate beneran, pakai endpoint POST /api/learning-path/generate lewat aplikasi.');

        return self::SUCCESS;
    }
}
