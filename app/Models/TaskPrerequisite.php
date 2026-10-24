<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskPrerequisite extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'task_id',
        'prerequisite_task_id',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function prerequisite(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'prerequisite_task_id');
    }
}
