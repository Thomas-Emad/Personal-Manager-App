<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('roadmap_id')->nullable()->constrained('roadmaps')->cascadeOnDelete();
            $table->foreignUuid('topic_id')->nullable()->constrained('topics')->cascadeOnDelete();
            $table->foreignUuid('task_id')->nullable()->constrained('tasks')->cascadeOnDelete();
            $table->string('title');
            $table->string('resource_type', 32)->default('url'); // url, note, text, attachment
            $table->string('category', 64)->default('general');
            $table->text('content_or_url')->nullable();
            $table->timestamps();
        });

        Schema::create('resource_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('storage_path', 512);
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('byte_size');
            $table->char('sha256_checksum', 64)->index();
            $table->timestamps();
        });

        Schema::create('spaced_repetition_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('topic_id')->constrained('topics')->cascadeOnDelete();
            $table->integer('repetition_number')->default(0);
            $table->float('ease_factor')->default(2.5);
            $table->integer('interval_days')->default(1);
            $table->dateTime('last_reviewed_at_utc')->nullable();
            $table->dateTime('next_review_at_utc')->index();
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('user_settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->text('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('spaced_repetition_items');
        Schema::dropIfExists('resource_attachments');
        Schema::dropIfExists('resources');
    }
};
