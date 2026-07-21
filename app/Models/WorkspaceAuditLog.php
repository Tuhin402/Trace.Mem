<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceAuditLog extends Model
{
    /**
     * Audit logs are append-only — no updated_at.
     */
    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'actor_user_id',
        'action',
        'subject_type',
        'subject_id',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'workspace_id'  => 'integer',
        'actor_user_id' => 'integer',
        'metadata'      => 'array',
        'created_at'    => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'workspace_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
