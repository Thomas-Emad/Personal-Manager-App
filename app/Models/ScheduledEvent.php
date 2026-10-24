<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScheduledEvent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'task_id',
        'title',
        'scheduled_start_utc',
        'scheduled_end_utc',
        'user_timezone',
        'reminder_lead_minutes',
        'status',
        'recurrence_rule',
        'notification_id',
    ];

    protected $casts = [
        'scheduled_start_utc' => 'datetime',
        'scheduled_end_utc' => 'datetime',
        'reminder_lead_minutes' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function studySession(): HasOne
    {
        return $this->hasOne(StudySession::class);
    }
}
