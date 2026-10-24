<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AvailabilityRule;
use App\Models\Lesson;
use App\Models\Phase;
use App\Models\Resource;
use App\Models\Roadmap;
use App\Models\ScheduledEvent;
use App\Models\SessionInterval;
use App\Models\SpacedRepetitionItem;
use App\Models\StudySession;
use App\Models\Task;
use App\Models\TaskPrerequisite;
use App\Models\Topic;
use App\Models\UserSetting;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Settings
        UserSetting::set('timezone', 'UTC');
        UserSetting::set('default_study_minutes', 45);
        UserSetting::set('default_break_minutes', 15);
        UserSetting::set('notifications_enabled', true);

        // 2. Availability rules
        for ($day = 1; $day <= 7; $day++) {
            AvailabilityRule::create([
                'day_of_week' => $day,
                'start_time' => '09:00',
                'end_time' => '18:00',
                'is_rest_day' => ($day === 5 || $day === 7), // Friday / Sunday rest days
                'preference_score' => ($day === 6 ? 2 : 1),
            ]);
        }

        // 3. Roadmap 1: Full-Stack Mobile & Architecture
        $roadmap = Roadmap::create([
            'title' => 'Advanced System Design & Mobile Architecture',
            'slug' => 'advanced-system-design-mobile-architecture',
            'description' => 'Comprehensive path mastering offline-first architectures, SQLite optimizations, and mobile design patterns.',
            'status' => 'active',
            'sort_order' => 0,
            'localized_content' => [
                'ar' => [
                    'title' => 'تصميم النظم المتقدمة وهندسة تطبيقات الجوال',
                    'description' => 'مسار تعليمي متكامل لإتقان المعماريات غير المتصلة (Offline-First) وتحسينات قواعد البيانات.',
                ],
            ],
        ]);

        // Phase 1
        $phase1 = Phase::create([
            'roadmap_id' => $roadmap->id,
            'title' => 'Foundations of Local-First Systems',
            'description' => 'Core concepts of distributed and offline storage.',
            'sort_order' => 0,
            'status' => 'in_progress',
        ]);

        $topic1 = Topic::create([
            'phase_id' => $phase1->id,
            'title' => 'SQLite Under The Hood: WAL & Concurrency',
            'description' => 'Deep dive into Write-Ahead Logging and transaction isolation.',
            'sort_order' => 0,
            'estimated_minutes' => 90,
            'status' => 'in_progress',
        ]);

        $lesson1 = Lesson::create([
            'topic_id' => $topic1->id,
            'title' => 'WAL vs Traditional Rollback Journal',
            'content' => 'Understanding how WAL allows concurrent readers and single writer.',
            'sort_order' => 0,
            'estimated_minutes' => 45,
            'status' => 'completed',
        ]);

        $task1 = Task::create([
            'lesson_id' => $lesson1->id,
            'title' => 'Analyze PRAGMA journal_mode=WAL performance',
            'description' => 'Compare read and write throughput in mobile embedded environments.',
            'task_type' => 'practice',
            'estimated_minutes' => 45,
            'sort_order' => 0,
            'status' => 'completed',
            'completed_at' => now()->subDays(1),
        ]);

        $task2 = Task::create([
            'lesson_id' => $lesson1->id,
            'title' => 'Implement conflict-free schema migrations',
            'description' => 'Write robust migrations that support safe rollback.',
            'task_type' => 'coding',
            'estimated_minutes' => 60,
            'sort_order' => 1,
            'status' => 'pending',
        ]);

        TaskPrerequisite::create([
            'task_id' => $task2->id,
            'prerequisite_task_id' => $task1->id,
        ]);

        // Topic 2: Spaced Repetition
        $topic2 = Topic::create([
            'phase_id' => $phase1->id,
            'title' => 'Spaced Repetition & Cognitive Recall',
            'description' => 'Algorithms for long-term knowledge retention.',
            'sort_order' => 1,
            'estimated_minutes' => 60,
            'status' => 'not_started',
        ]);

        $lesson2 = Lesson::create([
            'topic_id' => $topic2->id,
            'title' => 'SuperMemo-2 (SM-2) Mathematical Foundation',
            'content' => 'Ease factors, repetition counters, and review interval formulas.',
            'sort_order' => 0,
            'estimated_minutes' => 30,
            'status' => 'not_started',
        ]);

        $task3 = Task::create([
            'lesson_id' => $lesson2->id,
            'title' => 'Evaluate SM-2 ease factor adjustments',
            'description' => 'Test recall scores against interval projections.',
            'task_type' => 'reading',
            'estimated_minutes' => 30,
            'sort_order' => 0,
            'status' => 'pending',
        ]);

        // Spaced Repetition item for topic 1
        SpacedRepetitionItem::create([
            'topic_id' => $topic1->id,
            'repetition_number' => 2,
            'ease_factor' => 2.6,
            'interval_days' => 6,
            'last_reviewed_at_utc' => now()->subDays(2),
            'next_review_at_utc' => now()->addDays(4),
            'status' => 'active',
        ]);

        // 4. Scheduled Events for Today
        $now = Carbon::now();
        $eventToday1 = ScheduledEvent::create([
            'task_id' => $task2->id,
            'title' => $task2->title,
            'scheduled_start_utc' => $now->copy()->addMinutes(30)->toDateTimeString(),
            'scheduled_end_utc' => $now->copy()->addMinutes(90)->toDateTimeString(),
            'user_timezone' => 'UTC',
            'reminder_lead_minutes' => 15,
            'status' => 'scheduled',
        ]);

        $eventToday2 = ScheduledEvent::create([
            'task_id' => $task3->id,
            'title' => $task3->title,
            'scheduled_start_utc' => $now->copy()->addHours(3)->toDateTimeString(),
            'scheduled_end_utc' => $now->copy()->addHours(4)->toDateTimeString(),
            'user_timezone' => 'UTC',
            'reminder_lead_minutes' => 10,
            'status' => 'scheduled',
        ]);

        // 5. Past Completed Study Session with Real Rating
        $session = StudySession::create([
            'task_id' => $task1->id,
            'planned_start_utc' => now()->subDays(1)->setTime(10, 0),
            'planned_end_utc' => now()->subDays(1)->setTime(10, 45),
            'actual_start_utc' => now()->subDays(1)->setTime(10, 2),
            'actual_end_utc' => now()->subDays(1)->setTime(10, 48),
            'total_active_seconds' => 2640, // 44 minutes
            'status' => 'completed',
            'self_rating' => 9,
            'accomplishments' => 'Successfully verified WAL mode concurrent reader latency in SQLite on Android container.',
            'obstacles' => 'None, database performed cleanly under load.',
            'notes' => 'WAL mode is significantly faster than DELETE journal for mobile local storage.',
        ]);

        SessionInterval::create([
            'study_session_id' => $session->id,
            'interval_type' => 'active',
            'started_at_utc' => now()->subDays(1)->setTime(10, 2),
            'ended_at_utc' => now()->subDays(1)->setTime(10, 46),
            'duration_seconds' => 2640,
        ]);

        // 6. Resources in Study Library
        Resource::create([
            'roadmap_id' => $roadmap->id,
            'topic_id' => $topic1->id,
            'title' => 'Official SQLite WAL Documentation',
            'resource_type' => 'url',
            'category' => 'architecture',
            'content_or_url' => 'https://www.sqlite.org/wal.html',
        ]);

        Resource::create([
            'roadmap_id' => $roadmap->id,
            'topic_id' => $topic2->id,
            'title' => 'SuperMemo-2 Algorithm Specification',
            'resource_type' => 'url',
            'category' => 'algorithms',
            'content_or_url' => 'https://www.supermemo.com/en/archives1990-2015/english/ol/sm2',
        ]);

        Resource::create([
            'roadmap_id' => $roadmap->id,
            'title' => 'Personal Note: Offline Data Portability Best Practices',
            'resource_type' => 'note',
            'category' => 'notes',
            'content_or_url' => "Guidelines for portable data:\n1. Use RFC-4122 UUIDs for all primary keys.\n2. Store all timestamps in UTC.\n3. Encrypt exports with AES-256-GCM and PBKDF2/Argon2.",
        ]);
    }
}
