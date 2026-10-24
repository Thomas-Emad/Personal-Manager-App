<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrototypeItem extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'title',
        'details',
    ];
}
