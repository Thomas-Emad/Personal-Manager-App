<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AvailabilityRule;
use App\Models\ScheduledEvent;
use App\Models\Task;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class SchedulingService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Check for overlapping scheduled events in UTC.
     *
     * @return array<int, array{id: string, title: string, start: string, end: string}>
     */
    public function checkConflicts(string $startUtc, string $endUtc, ?string $excludeEventId = null): array
    {
        $start = Carbon::parse($startUtc);
        $end = Carbon::parse($endUtc);

        $query = ScheduledEvent::where('status', 'scheduled')
            ->where('scheduled_start_utc', '<', $end)
            ->where('scheduled_end_utc', '>', $start);

        if ($excludeEventId !== null) {
            $query->where('id', '!=', $excludeEventId);
        }

        return $query->get()->map(fn (ScheduledEvent $e) => [
            'id' => $e->id,
            'title' => $e->title,
            'start' => $e->scheduled_start_utc->toIso8601String(),
            'end' => $e->scheduled_end_utc->toIso8601String(),
        ])->all();
    }

    /**
     * Check if a given date falls on a configured rest day.
     */
    public function isRestDay(string $dateUtc): bool
    {
        $dayOfWeek = Carbon::parse($dateUtc)->dayOfWeekIso; // 1 (Mon) to 7 (Sun)

        return AvailabilityRule::where('day_of_week', $dayOfWeek)
            ->where('is_rest_day', true)
            ->exists();
    }

    /**
     * Convert a roadmap task into a scheduled calendar session.
     *
     * @throws Exception
     */
    public function scheduleTask(
        Task $task,
        string $startUtc,
        int $durationMinutes,
        string $userTimezone = 'UTC',
        int $reminderLeadMinutes = 15
    ): ScheduledEvent {
        $startTime = Carbon::parse($startUtc);
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        // Check prerequisites
        $unmetPrereqs = $task->prerequisites()->where('status', '!=', 'completed')->get();
        if ($unmetPrereqs->isNotEmpty()) {
            $titles = $unmetPrereqs->pluck('title')->implode(', ');
            throw new Exception("Cannot schedule task: unmet prerequisites ({$titles})");
        }

        $event = ScheduledEvent::create([
            'task_id' => $task->id,
            'title' => $task->title,
            'scheduled_start_utc' => $startTime->toDateTimeString(),
            'scheduled_end_utc' => $endTime->toDateTimeString(),
            'user_timezone' => $userTimezone,
            'reminder_lead_minutes' => $reminderLeadMinutes,
            'status' => 'scheduled',
        ]);

        // Schedule notification if trigger is in future
        $leadSeconds = $reminderLeadMinutes * 60;
        $triggerTimestamp = $startTime->getTimestamp() - $leadSeconds;
        $delaySeconds = $triggerTimestamp - now()->getTimestamp();

        if ($delaySeconds > 0) {
            try {
                $notif = $this->notificationService->schedule(
                    id: $event->id,
                    title: "Upcoming Study Session: {$task->title}",
                    body: "Starts in {$reminderLeadMinutes} minutes.",
                    delaySeconds: $delaySeconds,
                    data: ['event_id' => $event->id, 'task_id' => $task->id]
                );
                $event->update(['notification_id' => $notif['notification_id'] ?? null]);
            } catch (Exception) {
                // Non-blocking in dev
            }
        }

        $task->update(['status' => 'scheduled']);

        return $event;
    }

    /**
     * Reschedule an existing event non-destructively.
     */
    public function rescheduleEvent(ScheduledEvent $event, string $newStartUtc, string $newEndUtc): ScheduledEvent
    {
        // Cancel old notification
        if ($event->notification_id) {
            $this->notificationService->cancel($event->id);
        }

        $startTime = Carbon::parse($newStartUtc);
        $endTime = Carbon::parse($newEndUtc);

        $event->update([
            'scheduled_start_utc' => $startTime->toDateTimeString(),
            'scheduled_end_utc' => $endTime->toDateTimeString(),
            'status' => 'scheduled',
        ]);

        // Schedule new notification
        $delaySeconds = ($startTime->getTimestamp() - ($event->reminder_lead_minutes * 60)) - now()->getTimestamp();
        if ($delaySeconds > 0) {
            try {
                $notif = $this->notificationService->schedule(
                    id: $event->id,
                    title: "Rescheduled Session: {$event->title}",
                    body: "Starts in {$event->reminder_lead_minutes} minutes.",
                    delaySeconds: $delaySeconds,
                    data: ['event_id' => $event->id]
                );
                $event->update(['notification_id' => $notif['notification_id'] ?? null]);
            } catch (Exception) {
                // Non-blocking in dev
            }
        }

        return $event;
    }

    /**
     * Query events for a specific day.
     *
     * @return Collection<int, ScheduledEvent>
     */
    public function getEventsForDay(string $dateUtc): Collection
    {
        $dayStart = Carbon::parse($dateUtc)->startOfDay();
        $dayEnd = Carbon::parse($dateUtc)->endOfDay();

        return ScheduledEvent::with(['task.lesson.topic.phase.roadmap'])
            ->where('scheduled_start_utc', '>=', $dayStart->toDateTimeString())
            ->where('scheduled_start_utc', '<=', $dayEnd->toDateTimeString())
            ->orderBy('scheduled_start_utc')
            ->get();
    }
}
