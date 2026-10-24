<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'topic_id',
        'title',
        'content',
        'sort_order',
        'estimated_minutes',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'estimated_minutes' => 'integer',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('sort_order');
    }
}
