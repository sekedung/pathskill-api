<?php

namespace App\Services;

use App\Models\Career;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GroqService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key', '');
        // PENTING: llama-3.3-70b-versatile resmi di-deprecate Groq per 17 Juni 2026,
        // shutdown 16 Agustus 2026. Model default sekarang pakai pengganti resminya,
        // openai/gpt-oss-120b. Cek ulang https://console.groq.com/docs/deprecations
        // sebelum deploy — daftar model & tanggal shutdown Groq sering berubah.
        $this->model = config('services.groq.model', 'openai/gpt-oss-120b');
    }

    /**
     * Generate learning path (modul + lesson + assignment) berdasarkan
     * career tujuan dan skill gap user (hasil dari Skill Assessment).
     *
     * @param Career $career
     * @param array $skillGaps [['skill_name' => 'React', 'current' => 2, 'required' => 4.5], ...]
     * @return array{modules: array} — sudah divalidasi strukturnya, siap disimpan ke DB
     */
    public function generateLearningPath(Career $career, array $skillGaps): array
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('GROQ_API_KEY belum diset di .env');
        }

        $prompt = $this->buildPrompt($career, $skillGaps);

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post($this->baseUrl, [
                'model' => $this->model,
                'temperature' => 0.4,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Kamu adalah AI career coach yang menyusun learning path terstruktur untuk IT learner Indonesia. Balas HANYA dengan JSON valid, tanpa teks tambahan.',
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            Log::error('Groq API error', ['body' => $response->body()]);
            throw new RuntimeException('Gagal generate learning path dari Groq: ' . $response->status());
        }

        $content = $response->json('choices.0.message.content');

        // defensive: kadang model tetap membungkus JSON dengan markdown fence
        // meskipun sudah diminta response_format json_object
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim((string) $content));

        $decoded = json_decode($content, true);

        if (! is_array($decoded) || ! isset($decoded['modules'])) {
            throw new RuntimeException('Format respons Groq tidak sesuai ekspektasi.');
        }

        return $this->validateStructure($decoded);
    }

    private function buildPrompt(Career $career, array $skillGaps): string
    {
        $gapLines = collect($skillGaps)
            ->map(fn ($g) => "- {$g['skill_name']}: current {$g['current']}/5, industry butuh {$g['required']}/5")
            ->implode("\n");

        return <<<PROMPT
Career tujuan: {$career->name}

Skill gap user saat ini:
{$gapLines}

Buatkan learning path terstruktur (4-6 modul) untuk menutup skill gap di atas,
prioritaskan modul yang menutup gap terbesar dulu. Setiap modul harus punya:
- title (singkat, jelas)
- description (1 kalimat)
- lessons: array of string (judul lesson, 6-12 per modul)
- assignments: array of string (judul assignment praktis, 3-5 per modul)

Balas dalam format JSON PERSIS seperti ini (tanpa markdown, tanpa penjelasan tambahan):
{
  "modules": [
    {
      "title": "string",
      "description": "string",
      "lessons": ["string", "string"],
      "assignments": ["string", "string"]
    }
  ]
}
PROMPT;
    }

    /**
     * Validasi minimal supaya data dari AI tidak merusak database
     * kalau formatnya sedikit meleset (misal field kosong / bukan array).
     */
    private function validateStructure(array $decoded): array
    {
        $modules = array_filter($decoded['modules'], function ($m) {
            return is_array($m)
                && ! empty($m['title'])
                && ! empty($m['lessons'])
                && is_array($m['lessons'])
                && ! empty($m['assignments'])
                && is_array($m['assignments']);
        });

        if (empty($modules)) {
            throw new RuntimeException('Groq tidak mengembalikan modul yang valid.');
        }

        return ['modules' => array_values($modules)];
    }
}