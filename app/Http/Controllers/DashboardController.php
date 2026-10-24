<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Roadmap;
use App\Models\ScheduledEvent;
use App\Models\StudySession;
use App\Models\Task;
use App\Services\SpacedRepetitionService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(SpacedRepetitionService $srsService): Response
    {
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        // Today's scheduled events
        $todayEvents = ScheduledEvent::with(['task.lesson.topic.phase.roadmap'])
            ->whereBetween('scheduled_start_utc', [$todayStart, $todayEnd])
            ->orderBy('scheduled_start_utc')
            ->get();

        // Upcoming event
        $upcomingEvent = ScheduledEvent::with(['task'])
            ->where('scheduled_start_utc', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('scheduled_start_utc')
            ->first();

        $minutesUntilNext = $upcomingEvent
            ? (int) max(0, now()->diffInMinutes($upcomingEvent->scheduled_start_utc, false))
            : null;

        // Planned vs Actual Study Time Today (in minutes)
        $plannedMinutesToday = (int) $todayEvents->sum(function ($e) {
            return Carbon::parse($e->scheduled_start_utc)->diffInMinutes(Carbon::parse($e->scheduled_end_utc));
        });

        $actualSecondsToday = (int) StudySession::whereBetween('created_at', [$todayStart, $todayEnd])
            ->sum('total_active_seconds');
        $actualMinutesToday = (int) round($actualSecondsToday / 60);

        // Activity counts
        $completedToday = StudySession::whereBetween('created_at', [$todayStart, $todayEnd])
            ->where('status', 'completed')
            ->count();

        $pendingToday = $todayEvents->where('status', 'scheduled')->count();

        $overdueEvents = ScheduledEvent::where('status', 'scheduled')
            ->where('scheduled_end_utc', '<', now())
            ->count();

        // Check if there is an active study session in progress
        $activeSession = StudySession::with(['task', 'scheduledEvent'])
            ->where('status', 'in_progress')
            ->latest()
            ->first();

        // Assigned roadmap tasks
        $assignedTasks = Task::with(['lesson.topic.phase.roadmap'])
            ->whereIn('status', ['pending', 'scheduled', 'in_progress'])
            ->latest()
            ->take(6)
            ->get();

        // Spaced repetition due items
        $dueSrsItems = $srsService->getDueItems()->take(5);

        // Active roadmaps summary
        $roadmaps = Roadmap::where('status', 'active')->orderBy('sort_order')->take(4)->get();

        return Inertia::render('Dashboard', [
            'todayEvents' => $todayEvents,
            'upcomingEvent' => $upcomingEvent,
            'minutesUntilNext' => $minutesUntilNext,
            'plannedMinutesToday' => $plannedMinutesToday,
            'actualMinutesToday' => $actualMinutesToday,
            'completedToday' => $completedToday,
            'pendingToday' => $pendingToday,
            'overdueCount' => $overdueEvents,
            'activeSession' => $activeSession,
            'assignedTasks' => $assignedTasks,
            'dueSrsItems' => $dueSrsItems,
            'roadmaps' => $roadmaps,
        ]);
    }
}
