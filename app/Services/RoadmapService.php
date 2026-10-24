<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Roadmap;
use App\Models\Task;
use App\Models\TaskPrerequisite;
use Exception;
use Illuminate\Support\Str;

class RoadmapService
{
    /**
     * Create a new roadmap.
     *
     * @param  array<string, mixed>  $data
     */
    public function createRoadmap(array $data): Roadmap
    {
        $slug = Str::slug((string) ($data['title'] ?? 'roadmap-'.time()));
        $originalSlug = $slug;
        $count = 1;
        while (Roadmap::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return Roadmap::create([
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'localized_content' => $data['localized_content'] ?? null,
        ]);
    }

    /**
     * Add a prerequisite with cycle detection (Acyclic DAG Validation).
     *
     * @throws Exception
     */
    public function addPrerequisite(Task $task, Task $prerequisiteTask): void
    {
        if ($task->id === $prerequisiteTask->id) {
            throw new Exception('A task cannot depend on itself.');
        }

        // Cycle detection: check if $task is already an ancestor of $prerequisiteTask
        if ($this->isDescendantOf($prerequisiteTask->id, $task->id)) {
            throw new Exception('Circular dependency detected! Adding this prerequisite would create a cycle.');
        }

        TaskPrerequisite::firstOrCreate([
            'task_id' => $task->id,
            'prerequisite_task_id' => $prerequisiteTask->id,
        ]);
    }

    /**
     * Check if task $ancestorId is reached from $currentTaskId by traversing dependencies.
     */
    protected function isDescendantOf(string $currentTaskId, string $ancestorId): bool
    {
        $prereqs = TaskPrerequisite::where('task_id', $currentTaskId)->pluck('prerequisite_task_id');

        foreach ($prereqs as $prereqId) {
            if ($prereqId === $ancestorId) {
                return true;
            }

            if ($this->isDescendantOf($prereqId, $ancestorId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove a prerequisite.
     */
    public function removePrerequisite(Task $task, Task $prerequisiteTask): void
    {
        TaskPrerequisite::where('task_id', $task->id)
            ->where('prerequisite_task_id', $prerequisiteTask->id)
            ->delete();
    }

    /**
     * Reorder entities within a parent.
     *
     * @param  list<string>  $orderedIds
     */
    public function reorder(string $modelClass, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            $modelClass::where('id', $id)->update(['sort_order' => $index]);
        }
    }

    /**
     * Calculate progress metrics for a roadmap.
     *
     * @return array{total_tasks: int, completed_tasks: int, completion_rate: float, estimated_minutes_total: int, estimated_minutes_completed: int}
     */
    public function calculateRoadmapProgress(Roadmap $roadmap): array
    {
        $roadmap->load(['phases.topics.lessons.tasks']);

        $totalTasks = 0;
        $completedTasks = 0;
        $totalMinutes = 0;
        $completedMinutes = 0;

        foreach ($roadmap->phases as $phase) {
            foreach ($phase->topics as $topic) {
                foreach ($topic->lessons as $lesson) {
                    foreach ($lesson->tasks as $task) {
                        $totalTasks++;
                        $totalMinutes += $task->estimated_minutes;
                        if ($task->status === 'completed') {
                            $completedTasks++;
                            $completedMinutes += $task->estimated_minutes;
                        }
                    }
                }
            }
        }

        $rate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0.0;

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'completion_rate' => $rate,
            'estimated_minutes_total' => $totalMinutes,
            'estimated_minutes_completed' => $completedMinutes,
        ];
    }
}
