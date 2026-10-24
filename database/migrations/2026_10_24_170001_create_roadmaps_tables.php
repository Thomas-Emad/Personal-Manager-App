<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roadmaps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->integer('sort_order')->default(0);
            $table->text('localized_content')->nullable();
            $table->timestamps();
        });

        Schema::create('phases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('roadmap_id')->constrained('roadmaps')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('status', 32)->default('not_started');
            $table->timestamps();
        });

        Schema::create('topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('phase_id')->constrained('phases')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('estimated_minutes')->default(0);
            $table->string('status', 32)->default('not_started');
            $table->timestamps();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('topic_id')->constrained('topics')->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('estimated_minutes')->default(0);
            $table->string('status', 32)->default('not_started');
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('task_type', 32)->default('reading');
            $table->integer('estimated_minutes')->default(30);
            $table->integer('sort_order')->default(0);
            $table->string('status', 32)->default('pending')->index();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('task_prerequisites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignUuid('prerequisite_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'prerequisite_task_id']);
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('title');
            $table->integer('passing_score')->default(70);
            $table->integer('max_score')->default(100);
            $table->integer('last_score')->nullable();
            $table->boolean('passed')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('task_prerequisites');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('topics');
        Schema::dropIfExists('phases');
        Schema::dropIfExists('roadmaps');
    }
};
