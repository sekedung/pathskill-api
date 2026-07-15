<?php

namespace Database\Seeders;

use App\Models\Career;
use App\Models\LearningModule;
use Illuminate\Database\Seeder;

class LearningPathSeeder extends Seeder
{
    /**
     * Seed contoh learning path untuk "Full Stack Developer" saja,
     * sesuai isi prototype (5 modul). Career lain akan digenerate
     * oleh AI (Groq) nanti di fitur Learning Path.
     */
    public function run(): void
    {
        $career = Career::where('name', 'Full Stack Developer')->first();
        if (! $career) {
            return; // jalankan CareerSeeder dulu
        }

        $modules = [
            [
                'title' => 'Frontend Fundamentals',
                'description' => 'Master HTML, CSS, and JavaScript basics',
                'order' => 1,
                'lessons' => ['HTML Basics', 'CSS Fundamentals', 'Box Model', 'Flexbox', 'Grid Layout', 'JavaScript Basics', 'DOM Manipulation', 'Events'],
                'assignments' => ['Personal Portfolio Page', 'Responsive Landing Page', 'Interactive To-Do List'],
            ],
            [
                'title' => 'Modern JavaScript & ES6+',
                'description' => 'Deep dive into modern JavaScript features',
                'order' => 2,
                'lessons' => ['Arrow Functions', 'Destructuring', 'Spread & Rest', 'Promises', 'Async/Await', 'Modules', 'Array Methods', 'Closures', 'Classes', 'Fetch API'],
                'assignments' => ['Async Weather App', 'API Data Fetcher', 'Module Refactor Exercise', 'Quiz: ES6+ Concepts'],
            ],
            [
                'title' => 'React Essentials',
                'description' => 'Build interactive UI with React fundamentals',
                'order' => 3,
                'lessons' => ['JSX & Components', 'Props', 'State & useState', 'Event Handling', 'Conditional Rendering', 'Lists & Keys', 'Forms', 'useEffect', 'Component Lifecycle', 'Lifting State Up', 'Component Composition', 'React DevTools'],
                'assignments' => ['Todo App with React', 'Movie Search App', 'Multi-step Form', 'Shopping Cart', 'Component Library'],
            ],
            [
                'title' => 'TypeScript for React',
                'description' => 'Add type safety to your React applications',
                'order' => 4,
                'lessons' => ['TypeScript Basics', 'Types & Interfaces', 'Typing Props', 'Typing State & Hooks', 'Generics', 'Typing Events', 'Utility Types', 'TS Config for React'],
                'assignments' => ['Convert JS Project to TS', 'Typed Form Component', 'Typed API Client'],
            ],
            [
                'title' => 'Advanced React Patterns',
                'description' => 'Master hooks, context, and performance optimization',
                'order' => 5,
                'lessons' => ['Custom Hooks', 'Context API', 'useReducer', 'useMemo', 'useCallback', 'Code Splitting', 'Performance', 'Testing', 'Patterns', 'Architecture'],
                'assignments' => ['Custom Hook Library', 'State Management App', 'Performance Demo', 'Testing Suite'],
            ],
        ];

        foreach ($modules as $moduleData) {
            $lessons = $moduleData['lessons'];
            $assignments = $moduleData['assignments'];

            $module = LearningModule::create([
                'career_id' => $career->id,
                'title' => $moduleData['title'],
                'description' => $moduleData['description'],
                'order' => $moduleData['order'],
                'total_lessons' => count($lessons),
                'total_assignments' => count($assignments),
                'ai_generated' => true, // ditandai AI-generated sesuai prototype (badge "AI")
            ]);

            foreach ($lessons as $index => $title) {
                $module->lessons()->create([
                    'title' => "Lesson " . ($index + 1) . ": {$title}",
                    'type' => 'video',
                    'duration_minutes' => 15,
                    'order' => $index + 1,
                ]);
            }

            foreach ($assignments as $index => $title) {
                $module->assignments()->create([
                    'title' => 'Assignment ' . ($index + 1) . ": {$title}",
                    'description' => 'Complete a practical ' . strtolower($moduleData['title']) . ' project',
                    'due_date' => now()->addWeeks(($moduleData['order'] - 1) * 2 + $index + 1),
                    'order' => $index + 1,
                ]);
            }
        }
    }
}
