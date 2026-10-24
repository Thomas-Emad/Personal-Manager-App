<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Phase;
use App\Models\Roadmap;
use App\Models\Task;
use App\Models\Topic;
use App\Services\RoadmapService;
use App\Services\SchedulingService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoadmapController extends Controller
{
    public function __construct(
        protected RoadmapService $roadmapService,
        protected SchedulingService $schedulingService
    ) {}

    public function index(): Response
    {
        $roadmaps = Roadmap::with(['phases.topics.lessons.tasks'])
            ->orderBy('sort_order')
            ->get()
            ->map(function (Roadmap $r) {
                return [
                    'id' => $r->id,
                    'title' => $r->title,
                    'slug' => $r->slug,
                    'description' => $r->description,
                    'status' => $r->status,
                    'sort_order' => $r->sort_order,
                    'created_at' => $r->created_at->toIso8601String(),
                    'progress' => $this->roadmapService->calculateRoadmapProgress($r),
                ];
            });

        return Inertia::render('Roadmaps/Index', [
            'roadmaps' => $roadmaps,
        ]);
    }

    public function show(string $id): Response
    {
        $roadmap = Roadmap::with([
            'phases' => fn ($q) => $q->orderBy('sort_order'),
            'phases.topics' => fn ($q) => $q->orderBy('sort_order'),
            'phases.topics.lessons' => fn ($q) => $q->orderBy('sort_order'),
            'phases.topics.lessons.tasks' => fn ($q) => $q->orderBy('sort_order')->with(['prerequisites', 'assessments', 'resources']),
            'resources',
        ])->findOrFail($id);

        $progress = $this->roadmapService->calculateRoadmapProgress($roadmap);

        return Inertia::render('Roadmaps/Show', [
            'roadmap' => $roadmap,
            'progress' => $progress,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $roadmap = $this->roadmapService->createRoadmap($validated);

        return redirect()->route('roadmaps.show', $roadmap->id)->with('success', "Roadmap '{$roadmap->title}' created!");
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $roadmap = Roadmap::findOrFail($id);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,archived,completed'],
        ]);

        $roadmap->update($validated);

        return redirect()->back()->with('success', 'Roadmap updated successfully.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $roadmap = Roadmap::findOrFail($id);
        $title = $roadmap->title;
        $roadmap->delete();

        return redirect()->route('roadmaps.index')->with('success', "Roadmap '{$title}' deleted.");
    }

    public function storePhase(Request $request, string $roadmapId): RedirectResponse
    {
        $roadmap = Roadmap::findOrFail($roadmapId);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $phase = Phase::create([
            'roadmap_id' => $roadmap->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $roadmap->phases()->count(),
            'status' => 'not_started',
        ]);

        return redirect()->back()->with('success', "Phase '{$phase->title}' added.");
    }

    public function storeTopic(Request $request, string $phaseId): RedirectResponse
    {
        $phase = Phase::findOrFail($phaseId);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'estimated_minutes' => ['nullable', 'integer', 'min:5'],
        ]);

        $topic = Topic::create([
            'phase_id' => $phase->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'estimated_minutes' => (int) ($validated['estimated_minutes'] ?? 60),
            'sort_order' => $phase->topics()->count(),
            'status' => 'not_started',
        ]);

        return redirect()->back()->with('success', "Topic '{$topic->title}' added.");
    }

    public function storeLesson(Request $request, string $topicId): RedirectResponse
    {
        $topic = Topic::findOrFail($topicId);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'sort_order' => $topic->lessons()->count(),
            'status' => 'not_started',
        ]);

        return redirect()->back()->with('success', "Lesson '{$lesson->title}' added.");
    }

    public function storeTask(Request $request, string $lessonId): RedirectResponse
    {
        $lesson = Lesson::findOrFail($lessonId);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_type' => ['required', 'in:reading,coding,practice,project,review'],
            'estimated_minutes' => ['required', 'integer', 'min:5', 'max:600'],
        ]);

        $task = Task::create([
            'lesson_id' => $lesson->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'task_type' => $validated['task_type'],
            'estimated_minutes' => (int) $validated['estimated_minutes'],
            'sort_order' => $lesson->tasks()->count(),
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', "Task '{$task->title}' added.");
    }

    public function addPrerequisite(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'task_id' => ['required', 'exists:tasks,id'],
            'prerequisite_task_id' => ['required', 'exists:tasks,id'],
        ]);

        $task = Task::findOrFail($validated['task_id']);
        $prereq = Task::findOrFail($validated['prerequisite_task_id']);

        try {
            $this->roadmapService->addPrerequisite($task, $prereq);

            return redirect()->back()->with('success', "Prerequisite linked: '{$prereq->title}' -> '{$task->title}'");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function scheduleTask(Request $request, string $taskId): RedirectResponse
    {
        $task = Task::findOrFail($taskId);
        $validated = $request->validate([
            'start_utc' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:10', 'max:480'],
        ]);

        $duration = (int) ($validated['duration_minutes'] ?? $task->estimated_minutes);

        try {
            $event = $this->schedulingService->scheduleTask(
                $task,
                $validated['start_utc'],
                $duration
            );

            return redirect()->back()->with('success', "Task '{$task->title}' scheduled for ".$event->scheduled_start_utc->format('M j, H:i'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
