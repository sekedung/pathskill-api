<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Lesson;
use App\Models\UserAssignmentProgress;
use App\Models\UserModuleProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    /**
     * POST /api/lessons/{lesson}/complete
     * Tandai 1 lesson selesai, otomatis update percentage di modulnya.
     */
    public function completeLesson(Request $request, Lesson $lesson): JsonResponse
    {
        $user = $request->user();
        $module = $lesson->module;

        // catatan: untuk kesederhanaan, kita tidak menyimpan status per-lesson individual,
        // cukup increment percentage modul. Kalau butuh tracking per-lesson,
        // tambahkan tabel user_lesson_progress terpisah (pola sama seperti user_module_progress).
        $progress = UserModuleProgress::firstOrCreate(
            ['user_id' => $user->id, 'learning_module_id' => $module->id],
            ['status' => 'in_progress', 'percentage' => 0]
        );

        $increment = $module->total_lessons > 0 ? round(100 / $module->total_lessons) : 0;
        $newPercentage = min($progress->percentage + $increment, 100);

        $progress->update([
            'percentage' => $newPercentage,
            'status' => $newPercentage >= 100 ? 'completed' : 'in_progress',
        ]);

        return response()->json([
            'module_percentage' => $newPercentage,
            'module_status' => $progress->status,
        ]);
    }

    /**
     * POST /api/assignments/{assignment}/submit
     * Tandai assignment sudah di-submit (belum ada penyimpanan file asli —
     * lihat catatan di response untuk implementasi upload file yang sesungguhnya).
     */
    public function submitAssignment(Request $request, Assignment $assignment): JsonResponse
    {
        $user = $request->user();

        $progress = UserAssignmentProgress::updateOrCreate(
            ['user_id' => $user->id, 'assignment_id' => $assignment->id],
            ['status' => 'submitted']
        );

        return response()->json([
            'status' => $progress->status,
            'note' => 'File belum benar-benar disimpan — endpoint ini baru menandai status. Tambahkan Laravel Storage (disk s3/local) kalau perlu simpan file asli.',
        ]);
    }
}
