<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FreeTrialEvent — analytics event record for free trial lifecycle tracking.
 *
 * Event names:
 *   trial_viewed       — user viewed the offer on pricing or billing page
 *   trial_started      — user initiated checkout for a trial subscription
 *   trial_activated    — authentication confirmed; trial is live
 *   trial_cancelled    — user cancelled during the trial period
 *   trial_converted    — first real billing charge captured (trial → paid)
 *   trial_expired      — trial ended without conversion (webhook never fired)
 *   trial_upgraded     — user subscribed to a different plan during trial
 *   trial_downgraded   — user downgraded during trial
 *   trial_reminder_sent— a reminder email was dispatched
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $event_name
 * @property array|null $metadata
 */
class FreeTrialEvent extends Model
{
    protected $fillable = [
        'user_id',
        'event_name',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /* ── Relations ─────────────────────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
