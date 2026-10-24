<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assessment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'task_id',
        'title',
        'passing_score',
        'max_score',
        'last_score',
        'passed',
        'completed_at',
    ];

    protected $casts = [
        'passing_score' => 'integer',
        'max_score' => 'integer',
        'last_score' => 'integer',
        'passed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
