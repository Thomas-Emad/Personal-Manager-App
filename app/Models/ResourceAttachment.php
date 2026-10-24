<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceAttachment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'resource_id',
        'original_filename',
        'storage_path',
        'mime_type',
        'byte_size',
        'sha256_checksum',
    ];

    protected $casts = [
        'byte_size' => 'integer',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
