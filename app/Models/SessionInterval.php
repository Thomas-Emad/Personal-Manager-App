<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionInterval extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'study_session_id',
        'interval_type',
        'started_at_utc',
        'ended_at_utc',
        'duration_seconds',
    ];

    protected $casts = [
        'started_at_utc' => 'datetime',
        'ended_at_utc' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function studySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class);
    }
}
