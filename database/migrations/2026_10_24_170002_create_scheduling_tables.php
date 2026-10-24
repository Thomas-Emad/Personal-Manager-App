<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedTinyInteger('day_of_week')->index(); // 1=Mon .. 7=Sun
            $table->string('start_time', 5); // '09:00'
            $table->string('end_time', 5);   // '17:00'
            $table->boolean('is_rest_day')->default(false);
            $table->integer('preference_score')->default(1);
            $table->timestamps();
        });

        Schema::create('scheduled_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('title');
            $table->dateTime('scheduled_start_utc')->index();
            $table->dateTime('scheduled_end_utc')->index();
            $table->string('user_timezone', 64)->default('UTC');
            $table->integer('reminder_lead_minutes')->default(15);
            $table->string('status', 32)->default('scheduled')->index();
            $table->string('recurrence_rule')->nullable();
            $table->string('notification_id', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('study_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('scheduled_event_id')->nullable()->constrained('scheduled_events')->nullOnDelete();
            $table->foreignUuid('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->dateTime('planned_start_utc');
            $table->dateTime('planned_end_utc');
            $table->dateTime('actual_start_utc')->nullable();
            $table->dateTime('actual_end_utc')->nullable();
            $table->integer('total_active_seconds')->default(0);
            $table->string('status', 32)->default('in_progress')->index();
            $table->unsignedTinyInteger('self_rating')->nullable();
            $table->text('accomplishments')->nullable();
            $table->text('obstacles')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('session_intervals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('study_session_id')->constrained('study_sessions')->cascadeOnDelete();
            $table->string('interval_type', 16)->default('active'); // active, paused
            $table->dateTime('started_at_utc');
            $table->dateTime('ended_at_utc')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_intervals');
        Schema::dropIfExists('study_sessions');
        Schema::dropIfExists('scheduled_events');
        Schema::dropIfExists('availability_rules');
    }
};
