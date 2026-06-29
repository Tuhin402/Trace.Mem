<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    // ── Status constants ──────────────────────────────────────────────────────
    public const STATUS_QUEUED           = 'queued';
    public const STATUS_SENT             = 'sent';
    public const STATUS_DELIVERED        = 'delivered';
    public const STATUS_DELIVERY_DELAYED = 'delivery_delayed';
    public const STATUS_OPENED           = 'opened';
    public const STATUS_CLICKED          = 'clicked';
    public const STATUS_BOUNCED          = 'bounced';
    public const STATUS_COMPLAINED       = 'complained';
    public const STATUS_FAILED           = 'failed';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_message_id',
        'template_name',
        'template_version',
        'subject',
        'recipient_email',
        'sender_email',
        'request_id',
        'status',
        'queued_at',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'delayed_at',
        'bounced_at',
        'complained_at',
        'failed_at',
        'metadata',
    ];

    protected $casts = [
        'metadata'     => 'array',
        'queued_at'    => 'datetime',
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at'    => 'datetime',
        'clicked_at'   => 'datetime',
        'delayed_at'   => 'datetime',
        'bounced_at'   => 'datetime',
        'complained_at' => 'datetime',
        'failed_at'    => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
