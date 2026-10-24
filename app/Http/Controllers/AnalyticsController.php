<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Roadmap;
use App\Models\SpacedRepetitionItem;
use App\Models\StudySession;
use App\Models\Task;
use App\Services\RoadmapService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        protected RoadmapService $roadmapService
    ) {}

    public function index(): Response
    {
        $roadmaps = Roadmap::with(['phases.topics.lessons.tasks'])->get()->map(function (Roadmap $r) {
            return [
                'id' => $r->id,
                'title' => $r->title,
                'progress' => $this->roadmapService->calculateRoadmapProgress($r),
            ];
        });

        $totalActiveSeconds = (int) StudySession::sum('total_active_seconds');
        $totalHours = round($totalActiveSeconds / 3600, 1);

        $sessionsCount = StudySession::where('status', 'completed')->count();
        $averageRating = StudySession::whereNotNull('self_rating')->avg('self_rating');
        $averageRatingFormatted = $averageRating ? round((float) $averageRating, 1) : 0.0;

        // Last 7 days study duration (minutes per day)
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $seconds = (int) StudySession::whereBetween('created_at', [$dayStart, $dayEnd])->sum('total_active_seconds');
            $last7Days[] = [
                'date' => $date->format('M d'),
                'day' => $date->format('D'),
                'minutes' => (int) round($seconds / 60),
            ];
        }

        // Topics needing review: SRS items due or lowest rated sessions
        $topicsNeedingReview = SpacedRepetitionItem::with(['topic.phase.roadmap'])
            ->where('status', 'active')
            ->orderBy('next_review_at_utc')
            ->take(5)
            ->get();

        $totalTasks = Task::count();
        $completedTasks = Task::where('status', 'completed')->count();
        $globalCompletionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0.0;

        return Inertia::render('Analytics/Index', [
            'roadmaps' => $roadmaps,
            'totalHours' => $totalHours,
            'sessionsCount' => $sessionsCount,
            'averageRating' => $averageRatingFormatted,
            'last7Days' => $last7Days,
            'topicsNeedingReview' => $topicsNeedingReview,
            'globalCompletionRate' => $globalCompletionRate,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
        ]);
    }
}
