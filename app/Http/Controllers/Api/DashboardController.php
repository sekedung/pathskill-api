<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningModule;
use App\Models\UserAssignmentProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard
     * Ringkasan untuk halaman "Hi, {name}!" dashboard.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->career_goal_id) {
            return response()->json([
                'message' => 'User belum memilih career goal.',
            ], 422);
        }

        $modules = LearningModule::where('career_id', $user->career_goal_id)
            ->orderBy('order')
            ->with(['assignments', 'userProgress' => fn ($q) => $q->where('user_id', $user->id)])
            ->get();

        $totalModules = $modules->count();
        $completedModules = $modules->filter(
            fn ($m) => $m->userProgress->first()?->status === 'completed'
        )->count();

        $overallProgress = $totalModules > 0
            ? round($modules->avg(fn ($m) => $m->userProgress->first()?->percentage ?? 0))
            : 0;

        // semua assignment di modul-modul career ini
        $assignmentIds = $modules->flatMap(fn ($m) => $m->assignments->pluck('id'));

        $userAssignmentStatuses = UserAssignmentProgress::where('user_id', $user->id)
            ->whereIn('assignment_id', $assignmentIds)
            ->get()
            ->keyBy('assignment_id');

        $pendingAssignments = 0;
        $assignmentList = [];

        foreach ($modules as $module) {
            foreach ($module->assignments as $assignment) {
                $status = $userAssignmentStatuses[$assignment->id]->status ?? 'pending';
                if ($status !== 'successful') {
                    $pendingAssignments++;
                }
                $assignmentList[] = [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'module_title' => $module->title,
                    'due_date' => $assignment->due_date,
                    'status' => $status,
                ];
            }
        }

        // urutkan berdasarkan due_date terdekat, ambil 5 teratas untuk ditampilkan
        usort($assignmentList, fn ($a, $b) => strcmp((string) $a['due_date'], (string) $b['due_date']));
        $assignmentList = array_slice($assignmentList, 0, 5);

        // estimasi minggu tersisa: total minggu program (asumsi 2 minggu/modul) - minggu yang sudah lewat
        $estimatedTotalWeeks = $totalModules * 2;
        $weeksElapsed = $completedModules * 2;
        $weeksRemaining = max($estimatedTotalWeeks - $weeksElapsed, 0);

        $activeModules = $modules->take(3)->map(fn ($m) => [
            'id' => $m->id,
            'title' => $m->title,
            'total_lessons' => $m->total_lessons,
            'total_assignments' => $m->total_assignments,
            'ai_generated' => $m->ai_generated,
        ]);

        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'education_background' => $user->education_background,
                'career_goal' => $user->careerGoal?->name,
            ],
            'summary' => [
                'overall_progress' => $overallProgress, // persen 0-100
                'completed_modules' => $completedModules,
                'total_modules' => $totalModules,
                'pending_assignments' => $pendingAssignments,
                'weeks_remaining' => $weeksRemaining,
            ],
            'active_learning_path' => [
                'modules_completed' => $completedModules,
                'total_modules' => $totalModules,
                'progress_percentage' => $overallProgress,
                'modules' => $activeModules,
            ],
            'assignments_to_complete' => $assignmentList,
        ]);
    }
}
