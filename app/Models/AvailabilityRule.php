<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvailabilityRule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_rest_day',
        'preference_score',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_rest_day' => 'boolean',
        'preference_score' => 'integer',
    ];
}
