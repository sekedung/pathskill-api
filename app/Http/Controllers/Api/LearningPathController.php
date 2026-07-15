<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningModule;
use App\Services\GroqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LearningPathController extends Controller
{
    public function __construct(private GroqService $groq)
    {
    }

    /**
     * POST /api/learning-path/generate
     * Generate modul belajar pakai AI (Groq) berdasarkan skill gap user.
     * Kalau modul untuk career ini SUDAH ada (misal sudah pernah digenerate
     * user lain / sudah di-seed), langsung dipakai ulang — tidak generate dobel.
     */
    public function generate(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->career_goal_id) {
            return response()->json(['message' => 'User belum memilih career goal.'], 422);
        }

        $career = $user->careerGoal;

        $existingModules = LearningModule::where('career_id', $career->id)->exists();
        if ($existingModules) {
            return response()->json([
                'message' => 'Learning path untuk career ini sudah tersedia.',
                'generated' => false,
            ]);
        }

        // ambil skill gap dari hasil skill assessment user
        $skillGaps = $career->skills()
            ->with(['assessments' => fn ($q) => $q->where('user_id', $user->id)])
            ->get()
            ->map(fn ($skill) => [
                'skill_name' => $skill->skill_name,
                'current' => $skill->assessments->first()?->rating ?? 0,
                'required' => $skill->industry_requirement,
            ])
            ->toArray();

        if (empty($skillGaps)) {
            return response()->json([
                'message' => 'Belum ada data skill assessment untuk career ini.',
            ], 422);
        }

        try {
            $result = $this->groq->generateLearningPath($career, $skillGaps);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        DB::transaction(function () use ($result, $career) {
            foreach ($result['modules'] as $order => $moduleData) {
                $module = LearningModule::create([
                    'career_id' => $career->id,
                    'title' => $moduleData['title'],
                    'description' => $moduleData['description'] ?? null,
                    'order' => $order + 1,
                    'total_lessons' => count($moduleData['lessons']),
                    'total_assignments' => count($moduleData['assignments']),
                    'ai_generated' => true,
                ]);

                foreach ($moduleData['lessons'] as $i => $title) {
                    $module->lessons()->create([
                        'title' => 'Lesson ' . ($i + 1) . ": {$title}",
                        'type' => 'video',
                        'duration_minutes' => 15,
                        'order' => $i + 1,
                    ]);
                }

                foreach ($moduleData['assignments'] as $i => $title) {
                    $module->assignments()->create([
                        'title' => 'Assignment ' . ($i + 1) . ": {$title}",
                        'description' => "Complete a practical {$moduleData['title']} project",
                        'due_date' => now()->addWeeks(($order * 2) + $i + 1),
                        'order' => $i + 1,
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Learning path berhasil digenerate.',
            'generated' => true,
        ], 201);
    }

    /**
     * GET /api/learning-path
     * List modul untuk career user + progress overview
     * (dipakai di halaman "Your Learning Path").
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->career_goal_id) {
            return response()->json(['message' => 'User belum memilih career goal.'], 422);
        }

        $modules = LearningModule::where('career_id', $user->career_goal_id)
            ->orderBy('order')
            ->with(['userProgress' => fn ($q) => $q->where('user_id', $user->id)])
            ->get();

        $totalModules = $modules->count();
        $completedModules = $modules->filter(
            fn ($m) => $m->userProgress->first()?->status === 'completed'
        )->count();

        return response()->json([
            'overall_progress' => [
                'completed_modules' => $completedModules,
                'total_modules' => $totalModules,
            ],
            'total_lessons' => $modules->sum('total_lessons'),
            'total_assignments' => $modules->sum('total_assignments'),
            'estimated_duration_weeks' => $totalModules * 2,
            'modules' => $modules->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'total_lessons' => $m->total_lessons,
                'total_assignments' => $m->total_assignments,
                'ai_generated' => $m->ai_generated,
                'status' => $m->userProgress->first()?->status ?? 'not_started',
                'percentage' => $m->userProgress->first()?->percentage ?? 0,
            ]),
        ]);
    }

    /**
     * GET /api/learning-path/{module}
     * Detail 1 modul: lessons + assignments + status progress user
     * (dipakai di halaman "Advanced React Patterns" detail).
     */
    public function show(Request $request, LearningModule $module): JsonResponse
    {
        $user = $request->user();

        $module->load([
            'lessons',
            'assignments.userProgress' => fn ($q) => $q->where('user_id', $user->id),
            'userProgress' => fn ($q) => $q->where('user_id', $user->id),
        ]);

        return response()->json([
            'id' => $module->id,
            'title' => $module->title,
            'description' => $module->description,
            'progress_percentage' => $module->userProgress->first()?->percentage ?? 0,
            'lessons' => $module->lessons->map(fn ($l) => [
                'id' => $l->id,
                'title' => $l->title,
                'type' => $l->type,
                'duration_minutes' => $l->duration_minutes,
            ]),
            'assignments' => $module->assignments->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'description' => $a->description,
                'due_date' => $a->due_date,
                'status' => $a->userProgress->first()?->status ?? 'pending',
            ]),
        ]);
    }
}
