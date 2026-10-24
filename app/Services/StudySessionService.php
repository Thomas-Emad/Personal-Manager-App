<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ScheduledEvent;
use App\Models\SessionInterval;
use App\Models\StudySession;
use App\Models\Task;
use Carbon\Carbon;
use Exception;

class StudySessionService
{
    public function __construct(
        protected SpacedRepetitionService $srsService
    ) {}

    /**
     * Start a new study session from a scheduled event (or standalone task).
     */
    public function startSession(ScheduledEvent $event): StudySession
    {
        $session = StudySession::create([
            'scheduled_event_id' => $event->id,
            'task_id' => $event->task_id,
            'planned_start_utc' => $event->scheduled_start_utc,
            'planned_end_utc' => $event->scheduled_end_utc,
            'actual_start_utc' => now()->toDateTimeString(),
            'status' => 'in_progress',
        ]);

        // Start first active interval
        SessionInterval::create([
            'study_session_id' => $session->id,
            'interval_type' => 'active',
            'started_at_utc' => now()->toDateTimeString(),
        ]);

        $event->update(['status' => 'in_progress']);

        if ($event->task) {
            $event->task->update(['status' => 'in_progress']);
        }

        return $session;
    }

    /**
     * Pause an active study session.
     */
    public function pauseSession(StudySession $session): void
    {
        $activeInterval = $session->intervals()
            ->where('interval_type', 'active')
            ->whereNull('ended_at_utc')
            ->latest('started_at_utc')
            ->first();

        if ($activeInterval) {
            $endedAt = now();
            $duration = abs((int) Carbon::parse($activeInterval->started_at_utc)->diffInSeconds($endedAt));
            $activeInterval->update([
                'ended_at_utc' => $endedAt->toDateTimeString(),
                'duration_seconds' => $duration,
            ]);
        }

        // Start paused interval
        SessionInterval::create([
            'study_session_id' => $session->id,
            'interval_type' => 'paused',
            'started_at_utc' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Resume a paused study session.
     */
    public function resumeSession(StudySession $session): void
    {
        $pausedInterval = $session->intervals()
            ->where('interval_type', 'paused')
            ->whereNull('ended_at_utc')
            ->latest('started_at_utc')
            ->first();

        if ($pausedInterval) {
            $endedAt = now();
            $duration = abs((int) Carbon::parse($pausedInterval->started_at_utc)->diffInSeconds($endedAt));
            $pausedInterval->update([
                'ended_at_utc' => $endedAt->toDateTimeString(),
                'duration_seconds' => $duration,
            ]);
        }

        // Start active interval
        SessionInterval::create([
            'study_session_id' => $session->id,
            'interval_type' => 'active',
            'started_at_utc' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Conclude a study session and submit qualitative review.
     *
     * @param  array{self_rating: int, status: string, accomplishments?: string, obstacles?: string, notes?: string}  $review
     *
     * @throws Exception
     */
    public function finishSession(StudySession $session, array $review): StudySession
    {
        // Close any dangling interval
        $openInterval = $session->intervals()->whereNull('ended_at_utc')->first();
        if ($openInterval) {
            $endedAt = now();
            $duration = abs((int) Carbon::parse($openInterval->started_at_utc)->diffInSeconds($endedAt));
            $openInterval->update([
                'ended_at_utc' => $endedAt->toDateTimeString(),
                'duration_seconds' => $duration,
            ]);
        }

        // Sum active seconds (excluding pause intervals)
        $totalActiveSeconds = (int) $session->intervals()
            ->where('interval_type', 'active')
            ->sum('duration_seconds');

        $rating = (int) $review['self_rating'];
        if ($rating < 1 || $rating > 10) {
            throw new Exception('Self-rating must be an integer between 1 and 10.');
        }

        $status = $review['status'] ?? 'completed'; // completed, partially_completed

        $session->update([
            'actual_end_utc' => now()->toDateTimeString(),
            'total_active_seconds' => $totalActiveSeconds,
            'status' => $status,
            'self_rating' => $rating,
            'accomplishments' => $review['accomplishments'] ?? null,
            'obstacles' => $review['obstacles'] ?? null,
            'notes' => $review['notes'] ?? null,
        ]);

        if ($session->scheduledEvent) {
            $session->scheduledEvent->update(['status' => 'completed']);
        }

        if ($session->task && $status === 'completed') {
            $session->task->update([
                'status' => 'completed',
                'completed_at' => now()->toDateTimeString(),
            ]);

            // Update Spaced Repetition interval if topic is tracked
            $topic = $session->task->lesson?->topic;
            if ($topic) {
                $this->srsService->recordTopicReview($topic, $rating);
            }
        }

        return $session;
    }
}
