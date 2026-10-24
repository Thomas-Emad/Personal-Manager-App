<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lesson;
use App\Models\Phase;
use App\Models\Roadmap;
use App\Models\Task;
use App\Models\Topic;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoadmapImportService
{
    /**
     * Preview roadmap structure before import.
     *
     * @return array{title: string, description: string, phases_count: int, topics_count: int, tasks_count: int, estimated_minutes: int}
     *
     * @throws Exception
     */
    public function preview(string $jsonString): array
    {
        $data = json_decode($jsonString, true);
        if (! is_array($data) || empty($data['title'])) {
            throw new Exception('Invalid roadmap JSON: missing title.');
        }

        $phasesCount = count($data['phases'] ?? []);
        $topicsCount = 0;
        $tasksCount = 0;
        $totalMinutes = 0;

        foreach ($data['phases'] ?? [] as $phase) {
            $topics = $phase['topics'] ?? [];
            $topicsCount += count($topics);
            foreach ($topics as $topic) {
                foreach ($topic['lessons'] ?? [] as $lesson) {
                    $tasks = $lesson['tasks'] ?? [];
                    $tasksCount += count($tasks);
                    foreach ($tasks as $task) {
                        $totalMinutes += (int) ($task['estimated_minutes'] ?? 30);
                    }
                }
            }
        }

        return [
            'title' => (string) $data['title'],
            'description' => (string) ($data['description'] ?? ''),
            'phases_count' => $phasesCount,
            'topics_count' => $topicsCount,
            'tasks_count' => $tasksCount,
            'estimated_minutes' => $totalMinutes,
        ];
    }

    /**
     * Import a roadmap from structured JSON.
     * Generates fresh UUIDs and zeroed progress.
     *
     * @throws Exception
     */
    public function import(string $jsonString): Roadmap
    {
        $data = json_decode($jsonString, true);
        if (! is_array($data) || empty($data['title'])) {
            throw new Exception('Invalid roadmap JSON format.');
        }

        return DB::transaction(function () use ($data) {
            $slug = Str::slug((string) $data['title']);
            $count = 1;
            $originalSlug = $slug;
            while (Roadmap::where('slug', $slug)->exists()) {
                $slug = "{$originalSlug}-{$count}";
                $count++;
            }

            $roadmap = Roadmap::create([
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'status' => 'active',
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'localized_content' => $data['localized_content'] ?? null,
            ]);

            foreach ($data['phases'] ?? [] as $phaseIndex => $phaseData) {
                $phase = Phase::create([
                    'roadmap_id' => $roadmap->id,
                    'title' => $phaseData['title'] ?? 'Phase '.($phaseIndex + 1),
                    'description' => $phaseData['description'] ?? null,
                    'sort_order' => $phaseIndex,
                    'status' => 'not_started',
                ]);

                foreach ($phaseData['topics'] ?? [] as $topicIndex => $topicData) {
                    $topic = Topic::create([
                        'phase_id' => $phase->id,
                        'title' => $topicData['title'] ?? 'Topic '.($topicIndex + 1),
                        'description' => $topicData['description'] ?? null,
                        'sort_order' => $topicIndex,
                        'estimated_minutes' => (int) ($topicData['estimated_minutes'] ?? 60),
                        'status' => 'not_started',
                    ]);

                    foreach ($topicData['lessons'] ?? [] as $lessonIndex => $lessonData) {
                        $lesson = Lesson::create([
                            'topic_id' => $topic->id,
                            'title' => $lessonData['title'] ?? 'Lesson '.($lessonIndex + 1),
                            'content' => $lessonData['content'] ?? null,
                            'sort_order' => $lessonIndex,
                            'estimated_minutes' => (int) ($lessonData['estimated_minutes'] ?? 30),
                            'status' => 'not_started',
                        ]);

                        foreach ($lessonData['tasks'] ?? [] as $taskIndex => $taskData) {
                            Task::create([
                                'lesson_id' => $lesson->id,
                                'title' => $taskData['title'] ?? 'Task '.($taskIndex + 1),
                                'description' => $taskData['description'] ?? null,
                                'task_type' => $taskData['task_type'] ?? 'reading',
                                'estimated_minutes' => (int) ($taskData['estimated_minutes'] ?? 30),
                                'sort_order' => $taskIndex,
                                'status' => 'pending',
                            ]);
                        }
                    }
                }
            }

            return $roadmap;
        });
    }
}
