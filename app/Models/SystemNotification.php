<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemNotification extends Model
{
    protected $fillable = [
        'title',
        'message',
        'type',
        'component',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}
