<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudySession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'scheduled_event_id',
        'task_id',
        'planned_start_utc',
        'planned_end_utc',
        'actual_start_utc',
        'actual_end_utc',
        'total_active_seconds',
        'status',
        'self_rating',
        'accomplishments',
        'obstacles',
        'notes',
    ];

    protected $casts = [
        'planned_start_utc' => 'datetime',
        'planned_end_utc' => 'datetime',
        'actual_start_utc' => 'datetime',
        'actual_end_utc' => 'datetime',
        'total_active_seconds' => 'integer',
        'self_rating' => 'integer',
    ];

    public function scheduledEvent(): BelongsTo
    {
        return $this->belongsTo(ScheduledEvent::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function intervals(): HasMany
    {
        return $this->hasMany(SessionInterval::class)->orderBy('started_at_utc');
    }
}
