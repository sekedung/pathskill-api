<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Career;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillMapController extends Controller
{
    /**
     * GET /api/skill-map
     * Hitung "Your Skill Map": Tingkat Saat Ini, Tingkat yang Diperlukan,
     * Kesenjangan Keterampilan, plus data per-skill untuk radar chart.
     * Menggunakan career_goal_id milik user yang sedang login.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->career_goal_id) {
            return response()->json([
                'message' => 'User belum memilih career goal.',
            ], 422);
        }

        $career = Career::findOrFail($user->career_goal_id);

        $skills = $career->skills()
            ->with(['assessments' => fn ($q) => $q->where('user_id', $user->id)])
            ->get();

        // hanya hitung skill yang sudah dinilai (skip "Not rated") supaya rata-rata tidak bias ke 0
        $ratedSkills = $skills->filter(fn ($skill) => $skill->assessments->isNotEmpty());

        if ($ratedSkills->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada skill yang dinilai untuk career ini.',
            ], 422);
        }

        $currentLevel = round($ratedSkills->avg(fn ($skill) => $skill->assessments->first()->rating), 1);
        $requiredLevel = round($ratedSkills->avg('industry_requirement'), 1);
        $skillGap = round(max($requiredLevel - $currentLevel, 0), 1);

        $chartData = $skills->map(function ($skill) {
            return [
                'skill_name' => $skill->skill_name,
                'current' => $skill->assessments->first()?->rating ?? 0,
                'required' => $skill->industry_requirement,
            ];
        });

        return response()->json([
            'career' => $career->only(['id', 'name', 'icon']),
            'summary' => [
                'current_level' => $currentLevel,
                'required_level' => $requiredLevel,
                'skill_gap' => $skillGap,
            ],
            'chart_data' => $chartData,
        ]);
    }
}
