<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'lesson_id',
        'title',
        'description',
        'task_type',
        'estimated_minutes',
        'sort_order',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'estimated_minutes' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_prerequisites',
            'task_id',
            'prerequisite_task_id'
        );
    }

    public function dependentTasks(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_prerequisites',
            'prerequisite_task_id',
            'task_id'
        );
    }

    public function scheduledEvents(): HasMany
    {
        return $this->hasMany(ScheduledEvent::class);
    }

    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}
