<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AvailabilityRule;
use App\Models\ScheduledEvent;
use App\Models\Task;
use App\Services\NotificationService;
use App\Services\SchedulingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __construct(
        protected SchedulingService $schedulingService,
        protected NotificationService $notificationService
    ) {}

    public function index(Request $request): Response
    {
        $view = (string) $request->get('view', 'week'); // 'day', 'week', 'month'
        $selectedDate = (string) $request->get('date', now()->toDateString());

        $current = Carbon::parse($selectedDate);

        if ($view === 'day') {
            $startRange = $current->copy()->startOfDay();
            $endRange = $current->copy()->endOfDay();
        } elseif ($view === 'month') {
            $startRange = $current->copy()->startOfMonth()->startOfWeek();
            $endRange = $current->copy()->endOfMonth()->endOfWeek();
        } else {
            // week (default)
            $startRange = $current->copy()->startOfWeek();
            $endRange = $current->copy()->endOfWeek();
        }

        $events = ScheduledEvent::with(['task.lesson.topic.phase.roadmap'])
            ->whereBetween('scheduled_start_utc', [$startRange->toDateTimeString(), $endRange->toDateTimeString()])
            ->orderBy('scheduled_start_utc')
            ->get();

        $availabilityRules = AvailabilityRule::orderBy('day_of_week')->get();
        if ($availabilityRules->isEmpty()) {
            // Seed standard 7-day availability if empty
            for ($d = 1; $d <= 7; $d++) {
                AvailabilityRule::create([
                    'day_of_week' => $d,
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'is_rest_day' => ($d === 5 || $d === 7), // Friday or Sunday as default rest days
                    'preference_score' => 1,
                ]);
            }
            $availabilityRules = AvailabilityRule::orderBy('day_of_week')->get();
        }

        $schedulableTasks = Task::whereIn('status', ['pending', 'scheduled'])
            ->with(['lesson.topic.phase.roadmap'])
            ->latest()
            ->take(15)
            ->get();

        return Inertia::render('Calendar/Index', [
            'view' => $view,
            'selectedDate' => $selectedDate,
            'rangeStart' => $startRange->toDateString(),
            'rangeEnd' => $endRange->toDateString(),
            'events' => $events,
            'availabilityRules' => $availabilityRules,
            'schedulableTasks' => $schedulableTasks,
        ]);
    }

    public function storeEvent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'scheduled_start_utc' => ['required', 'date'],
            'scheduled_end_utc' => ['required', 'date', 'after:scheduled_start_utc'],
            'task_id' => ['nullable', 'exists:tasks,id'],
            'reminder_lead_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
        ]);

        // Conflict check
        $conflicts = $this->schedulingService->checkConflicts(
            $validated['scheduled_start_utc'],
            $validated['scheduled_end_utc']
        );

        $event = ScheduledEvent::create([
            'title' => $validated['title'],
            'task_id' => $validated['task_id'] ?? null,
            'scheduled_start_utc' => $validated['scheduled_start_utc'],
            'scheduled_end_utc' => $validated['scheduled_end_utc'],
            'user_timezone' => config('app.timezone', 'UTC'),
            'reminder_lead_minutes' => (int) ($validated['reminder_lead_minutes'] ?? 15),
            'status' => 'scheduled',
        ]);

        if (! empty($validated['task_id'])) {
            Task::where('id', $validated['task_id'])->update(['status' => 'scheduled']);
        }

        // Schedule notification
        $startTime = Carbon::parse($validated['scheduled_start_utc']);
        $leadSeconds = ((int) ($validated['reminder_lead_minutes'] ?? 15)) * 60;
        $delaySeconds = ($startTime->getTimestamp() - $leadSeconds) - now()->getTimestamp();

        if ($delaySeconds > 0) {
            try {
                $notif = $this->notificationService->schedule(
                    id: $event->id,
                    title: "Upcoming Session: {$event->title}",
                    body: "Starts in {$event->reminder_lead_minutes} minutes.",
                    delaySeconds: $delaySeconds,
                    data: ['event_id' => $event->id]
                );
                $event->update(['notification_id' => $notif['notification_id'] ?? null]);
            } catch (Exception) {
                // Ignore dev notifications
            }
        }

        $warning = ! empty($conflicts) ? ' (Note: Detected overlap with another scheduled session)' : '';

        return redirect()->back()->with('success', "Event '{$event->title}' scheduled successfully!{$warning}");
    }

    public function reschedule(Request $request, string $id): RedirectResponse
    {
        $event = ScheduledEvent::findOrFail($id);

        $validated = $request->validate([
            'scheduled_start_utc' => ['required', 'date'],
            'scheduled_end_utc' => ['required', 'date', 'after:scheduled_start_utc'],
        ]);

        $this->schedulingService->rescheduleEvent(
            $event,
            $validated['scheduled_start_utc'],
            $validated['scheduled_end_utc']
        );

        return redirect()->back()->with('success', "Session '{$event->title}' rescheduled successfully.");
    }

    public function destroy(string $id): RedirectResponse
    {
        $event = ScheduledEvent::findOrFail($id);

        if ($event->notification_id) {
            $this->notificationService->cancel($event->id);
        }

        $title = $event->title;
        $event->delete();

        return redirect()->back()->with('success', "Event '{$title}' cancelled.");
    }

    public function updateAvailability(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rules' => ['required', 'array'],
            'rules.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'rules.*.start_time' => ['required', 'string'],
            'rules.*.end_time' => ['required', 'string'],
            'rules.*.is_rest_day' => ['required', 'boolean'],
        ]);

        foreach ($validated['rules'] as $ruleData) {
            AvailabilityRule::updateOrCreate(
                ['day_of_week' => $ruleData['day_of_week']],
                [
                    'start_time' => $ruleData['start_time'],
                    'end_time' => $ruleData['end_time'],
                    'is_rest_day' => $ruleData['is_rest_day'],
                ]
            );
        }

        return redirect()->back()->with('success', 'Weekly availability rules updated successfully.');
    }
}
