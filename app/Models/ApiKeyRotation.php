<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKeyRotation extends Model
{
    protected $fillable = [
        'api_key_id',
        'replaced_by_api_key_id',
        'reason',
        'metadata',
        'rotated_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'rotated_at' => 'datetime',
    ];
}