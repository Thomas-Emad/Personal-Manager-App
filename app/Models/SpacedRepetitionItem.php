<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpacedRepetitionItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'topic_id',
        'repetition_number',
        'ease_factor',
        'interval_days',
        'last_reviewed_at_utc',
        'next_review_at_utc',
        'status',
    ];

    protected $casts = [
        'repetition_number' => 'integer',
        'ease_factor' => 'float',
        'interval_days' => 'integer',
        'last_reviewed_at_utc' => 'datetime',
        'next_review_at_utc' => 'datetime',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
