<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Topic extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'phase_id',
        'title',
        'description',
        'sort_order',
        'estimated_minutes',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'estimated_minutes' => 'integer',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order');
    }

    public function spacedRepetitionItem(): HasOne
    {
        return $this->hasOne(SpacedRepetitionItem::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}
