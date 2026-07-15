<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Lesson;
use App\Models\UserAssignmentProgress;
use App\Models\UserLessonProgress;
use App\Models\UserModuleProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProgressController extends Controller
{
    /**
     * POST /api/lessons/{lesson}/complete
     * Tandai 1 lesson selesai. Idempotent — kalau sudah pernah ditandai
     * selesai sebelumnya, tidak akan menambah persentase modul dua kali.
     * Persentase modul dihitung ulang dari total lesson yang benar-benar
     * completed (bukan sekadar increment), jadi akurat walau lesson
     * di-refresh/diklik berkali-kali atau di-load ulang.
     */
    public function completeLesson(Request $request, Lesson $lesson): JsonResponse
    {
        $user = $request->user();
        $module = $lesson->module;

        UserLessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['completed' => true, 'completed_at' => now()]
        );

        $completedCount = UserLessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $module->lessons()->pluck('id'))
            ->where('completed', true)
            ->count();

        $percentage = $module->total_lessons > 0
            ? (int) round(($completedCount / $module->total_lessons) * 100)
            : 0;

        $progress = UserModuleProgress::updateOrCreate(
            ['user_id' => $user->id, 'learning_module_id' => $module->id],
            [
                'percentage' => $percentage,
                'status' => $percentage >= 100 ? 'completed' : ($percentage > 0 ? 'in_progress' : 'not_started'),
            ]
        );

        return response()->json([
            'lesson_completed' => true,
            'module_percentage' => $percentage,
            'module_status' => $progress->status,
            'lessons_completed' => $completedCount,
            'total_lessons' => $module->total_lessons,
        ]);
    }

    /**
     * POST /api/assignments/{assignment}/submit
     * Upload file assignment asli (multipart/form-data, field: "file")
     * dan tandai status jadi 'submitted'.
     *
     * Catatan: pakai disk 'public' — jalankan `php artisan storage:link`
     * sekali di server supaya file bisa diakses via URL publik.
     */
    public function submitAssignment(Request $request, Assignment $assignment): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,zip,jpg,jpeg,png'],
        ], [
            'file.required' => 'File tugas wajib diupload.',
            'file.max' => 'Ukuran file maksimal 10MB.',
            'file.mimes' => 'Format file harus pdf, doc, docx, zip, jpg, jpeg, atau png.',
        ]);

        // hapus file lama kalau user submit ulang (resubmit)
        $existing = UserAssignmentProgress::where('user_id', $user->id)
            ->where('assignment_id', $assignment->id)
            ->first();
        if ($existing?->file_path) {
            Storage::disk('public')->delete($existing->file_path);
        }

        $path = $validated['file']->store('assignments/' . $user->id, 'public');

        $progress = UserAssignmentProgress::updateOrCreate(
            ['user_id' => $user->id, 'assignment_id' => $assignment->id],
            [
                'status' => 'submitted',
                'file_path' => $path,
                'file_name' => $validated['file']->getClientOriginalName(),
                'submitted_at' => now(),
            ]
        );

        return response()->json([
            'status' => $progress->status,
            'file_name' => $progress->file_name,
            'file_url' => Storage::disk('public')->url($path),
        ]);
    }
}