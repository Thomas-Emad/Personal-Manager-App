<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Phase;
use App\Models\Roadmap;
use App\Models\ScheduledEvent;
use App\Models\Task;
use App\Models\Topic;
use App\Services\BackupService;
use App\Services\RoadmapService;
use App\Services\SchedulingService;
use App\Services\SpacedRepetitionService;
use App\Services\StudySessionService;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ApplicationModulesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Module A: Today Dashboard renders with real database items.
     */
    public function test_dashboard_renders_with_real_data(): void
    {
        $roadmap = Roadmap::create([
            'title' => 'Mobile Architecture Mastery',
            'slug' => 'mobile-mastery',
        ]);

        $phase = Phase::create([
            'roadmap_id' => $roadmap->id,
            'title' => 'Phase 1',
            'sort_order' => 1,
        ]);

        $topic = Topic::create([
            'phase_id' => $phase->id,
            'title' => 'SQLite Optimizations',
            'sort_order' => 1,
        ]);

        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'title' => 'WAL Mode Deep Dive',
            'sort_order' => 1,
        ]);

        $task = Task::create([
            'lesson_id' => $lesson->id,
            'title' => 'Configure SQLite WAL journal mode',
            'estimated_minutes' => 45,
            'status' => 'pending',
            'sort_order' => 1,
        ]);

        $todayStart = Carbon::today()->setTime(10, 0);
        ScheduledEvent::create([
            'task_id' => $task->id,
            'title' => 'Study SQLite WAL',
            'scheduled_start_utc' => $todayStart->toIso8601String(),
            'scheduled_end_utc' => $todayStart->copy()->addMinutes(45)->toIso8601String(),
            'status' => 'scheduled',
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('todayEvents', 1)
                ->where('plannedMinutesToday', 45)
        );
    }

    /**
     * Module B & F: Calendar Scheduling and Conflict Detection.
     */
    public function test_calendar_schedules_event_and_detects_conflict(): void
    {
        $start = Carbon::today()->addDay()->setTime(14, 0);
        $end = $start->copy()->addMinutes(60);

        // Schedule first event
        $firstEvent = ScheduledEvent::create([
            'title' => 'Vue 3 Reactivity',
            'scheduled_start_utc' => $start->toIso8601String(),
            'scheduled_end_utc' => $end->toIso8601String(),
            'status' => 'scheduled',
        ]);

        // Attempt overlapping event via service
        $schedulingService = app(SchedulingService::class);
        $overlapStart = $start->copy()->addMinutes(30);
        $overlapEnd = $overlapStart->copy()->addMinutes(60);

        $conflicts = $schedulingService->checkConflicts(
            $overlapStart->toIso8601String(),
            $overlapEnd->toIso8601String()
        );

        $this->assertCount(1, $conflicts);
        $this->assertSame($firstEvent->id, $conflicts[0]['id']);
    }

    /**
     * Module C: Roadmap hierarchy and cycle prevention.
     */
    public function test_roadmap_hierarchy_and_prerequisite_cycle_prevention(): void
    {
        $roadmapService = app(RoadmapService::class);

        $roadmap = Roadmap::create(['title' => 'Fullstack Road', 'slug' => 'fullstack']);
        $phase = Phase::create(['roadmap_id' => $roadmap->id, 'title' => 'Foundation', 'sort_order' => 1]);
        $topic = Topic::create(['phase_id' => $phase->id, 'title' => 'Basics', 'sort_order' => 1]);
        $lesson = Lesson::create(['topic_id' => $topic->id, 'title' => 'Core Concepts', 'sort_order' => 1]);

        $taskA = Task::create(['lesson_id' => $lesson->id, 'title' => 'Task A', 'sort_order' => 1]);
        $taskB = Task::create(['lesson_id' => $lesson->id, 'title' => 'Task B', 'sort_order' => 2]);
        $taskC = Task::create(['lesson_id' => $lesson->id, 'title' => 'Task C', 'sort_order' => 3]);

        // A -> B (B depends on A)
        $roadmapService->addPrerequisite($taskB, $taskA);
        // B -> C (C depends on B)
        $roadmapService->addPrerequisite($taskC, $taskB);

        // Attempt cycle: A depends on C (C -> A creates cycle A -> B -> C -> A)
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Circular dependency detected');
        $roadmapService->addPrerequisite($taskA, $taskC);
    }

    /**
     * Module E: Study Session state machine & active duration math (excluding pause).
     */
    public function test_study_session_lifecycle_and_duration(): void
    {
        $sessionService = app(StudySessionService::class);

        $event = ScheduledEvent::create([
            'title' => 'Autonomous Focus Session',
            'scheduled_start_utc' => now()->toIso8601String(),
            'scheduled_end_utc' => now()->addMinutes(60)->toIso8601String(),
            'status' => 'scheduled',
        ]);

        $session = $sessionService->startSession($event);
        $this->assertSame('in_progress', $session->status);
        $this->assertCount(1, $session->intervals);

        // Advance 10 minutes, then pause
        Carbon::setTestNow(now()->addMinutes(10));
        $sessionService->pauseSession($session);

        // Advance 15 minutes while paused, then resume
        Carbon::setTestNow(now()->addMinutes(15));
        $sessionService->resumeSession($session);

        // Focus for another 20 minutes, then finish
        Carbon::setTestNow(now()->addMinutes(20));
        $finishedSession = $sessionService->finishSession($session, [
            'status' => 'completed',
            'self_rating' => 9,
            'accomplishments' => 'Finished mobile UI implementation',
            'obstacles' => 'None',
        ]);

        $this->assertSame('completed', $finishedSession->status);
        $this->assertSame(9, $finishedSession->self_rating);
        // Total active time: 10m (600s) + 20m (1200s) = 1800s
        $this->assertEqualsWithDelta(1800, $finishedSession->total_active_seconds, 2);

        Carbon::setTestNow(); // Reset test clock
    }

    /**
     * Module I: Spaced Repetition SuperMemo-2 algorithm.
     */
    public function test_spaced_repetition_supermemo_calculation(): void
    {
        $roadmap = Roadmap::create(['title' => 'SRS Test', 'slug' => 'srs-test']);
        $phase = Phase::create(['roadmap_id' => $roadmap->id, 'title' => 'P1', 'sort_order' => 1]);
        $topic = Topic::create(['phase_id' => $phase->id, 'title' => 'Topic SRS', 'sort_order' => 1]);

        $srsService = app(SpacedRepetitionService::class);

        // First review with excellent rating (10/10 -> grade 5)
        $item1 = $srsService->recordTopicReview($topic, 10);
        $this->assertSame(1, $item1->repetition_number);
        $this->assertSame(1, $item1->interval_days);

        // Second review with high rating (9/10 -> grade 5)
        $item2 = $srsService->recordTopicReview($topic, 9);
        $this->assertSame(2, $item2->repetition_number);
        $this->assertSame(6, $item2->interval_days);

        // Third review with high rating (9/10 -> grade 5) -> interval = 6 * 2.6 = 16 days
        $item3 = $srsService->recordTopicReview($topic, 9);
        $this->assertSame(3, $item3->repetition_number);
        $this->assertSame(16, $item3->interval_days);
    }

    /**
     * Module G: Study Library stores and filters resources.
     */
    public function test_study_library_stores_and_filters_resources(): void
    {
        $response = $this->post(route('library.store'), [
            'title' => 'NativePHP Mobile Documentation',
            'resource_type' => 'url',
            'category' => 'documentation',
            'content_or_url' => 'https://nativephp.com/docs/mobile',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('resources', [
            'title' => 'NativePHP Mobile Documentation',
            'resource_type' => 'url',
            'category' => 'documentation',
        ]);

        $libraryIndex = $this->get(route('library.index', ['category' => 'documentation']));
        $libraryIndex->assertStatus(200);
        $libraryIndex->assertInertia(
            fn (Assert $page) => $page
                ->component('Library/Index')
                ->has('resources', 1)
                ->where('currentCategory', 'documentation')
        );
    }

    /**
     * Module J: Backup Archive Export & Restore Roundtrip (with AES-256 encryption).
     */
    public function test_backup_archive_export_and_restore_roundtrip(): void
    {
        $backupService = app(BackupService::class);

        Roadmap::create(['title' => 'Backup Subject Roadmap', 'slug' => 'backup-subject']);

        $password = 'SecretKey2026!';
        $archivePath = $backupService->exportBackup($password);

        $this->assertFileExists($archivePath);
        $this->assertStringEndsWith('.pma', $archivePath);

        // Clean database by deleting roadmap
        Roadmap::query()->delete();
        $this->assertDatabaseCount('roadmaps', 0);

        // Restore backup
        $result = $backupService->importBackup($archivePath, 'replace', $password);
        $this->assertTrue($result['success']);

        // Verify roadmap restored
        $this->assertDatabaseHas('roadmaps', [
            'title' => 'Backup Subject Roadmap',
        ]);

        // Clean up exported archive file
        if (file_exists($archivePath)) {
            unlink($archivePath);
        }
    }

    /**
     * Module K: Locale toggle between English (LTR) and Arabic (RTL).
     */
    public function test_locale_toggle_arabic_and_english(): void
    {
        $response = $this->post(route('settings.locale'), ['locale' => 'ar']);
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'ar');
        $this->assertSame('ar', app()->getLocale());

        $responseEn = $this->post(route('settings.locale'), ['locale' => 'en']);
        $responseEn->assertRedirect();
        $responseEn->assertSessionHas('locale', 'en');
        $this->assertSame('en', app()->getLocale());
    }
}
