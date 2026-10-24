<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Roadmap extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'title',
        'slug',
        'description',
        'status',
        'sort_order',
        'localized_content',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'localized_content' => 'array',
    ];

    public function phases(): HasMany
    {
        return $this->hasMany(Phase::class)->orderBy('sort_order');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}
