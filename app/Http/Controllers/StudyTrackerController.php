<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ScheduledEvent;
use App\Models\StudySession;
use App\Models\Task;
use App\Services\StudySessionService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudyTrackerController extends Controller
{
    public function __construct(
        protected StudySessionService $sessionService
    ) {}

    public function index(): Response
    {
        $activeSession = StudySession::with(['task.lesson.topic.phase.roadmap', 'scheduledEvent', 'intervals'])
            ->whereIn('status', ['in_progress', 'paused'])
            ->latest()
            ->first();

        $readyEvents = ScheduledEvent::with(['task.lesson.topic.phase.roadmap'])
            ->where('status', 'scheduled')
            ->where('scheduled_start_utc', '<=', now()->addHours(2))
            ->orderBy('scheduled_start_utc')
            ->get();

        $recentSessions = StudySession::with(['task.lesson.topic.phase.roadmap'])
            ->whereIn('status', ['completed', 'partially_completed'])
            ->latest('actual_end_utc')
            ->take(10)
            ->get();

        return Inertia::render('Tracker/Index', [
            'activeSession' => $activeSession,
            'readyEvents' => $readyEvents,
            'recentSessions' => $recentSessions,
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => ['nullable', 'exists:scheduled_events,id'],
            'task_id' => ['nullable', 'exists:tasks,id'],
        ]);

        if (! empty($validated['event_id'])) {
            $event = ScheduledEvent::findOrFail($validated['event_id']);
        } else {
            $task = Task::findOrFail($validated['task_id']);
            $event = ScheduledEvent::create([
                'task_id' => $task->id,
                'title' => $task->title,
                'scheduled_start_utc' => now(),
                'scheduled_end_utc' => now()->addMinutes($task->estimated_minutes),
                'status' => 'scheduled',
            ]);
        }

        $session = $this->sessionService->startSession($event);

        return redirect()->route('tracker.index')->with('success', "Study session started for '{$event->title}'!");
    }

    public function pause(string $id): RedirectResponse
    {
        $session = StudySession::findOrFail($id);
        $this->sessionService->pauseSession($session);
        $session->update(['status' => 'paused']);

        return redirect()->back()->with('success', 'Session paused.');
    }

    public function resume(string $id): RedirectResponse
    {
        $session = StudySession::findOrFail($id);
        $this->sessionService->resumeSession($session);
        $session->update(['status' => 'in_progress']);

        return redirect()->back()->with('success', 'Session resumed.');
    }

    public function finish(Request $request, string $id): RedirectResponse
    {
        $session = StudySession::findOrFail($id);

        $validated = $request->validate([
            'self_rating' => ['required', 'integer', 'between:1,10'],
            'status' => ['required', 'in:completed,partially_completed'],
            'accomplishments' => ['nullable', 'string'],
            'obstacles' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->sessionService->finishSession($session, $validated);

            return redirect()->route('tracker.index')->with('success', 'Study session completed and review saved!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
