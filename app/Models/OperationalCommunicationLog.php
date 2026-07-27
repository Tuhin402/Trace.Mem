<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalCommunicationLog extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Get the owning recipient model (User or Tenant).
     */
    public function recipient()
    {
        return $this->morphTo(null, 'recipient_type', 'recipient_uuid');
    }

    /**
     * Get the admin user who sent the communication.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
