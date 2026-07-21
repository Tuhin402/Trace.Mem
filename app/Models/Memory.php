<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $importance
 * @property string|null $confidence
 * @property string|null $decay_score
 * @property string|null $last_accessed_at
 * @property int|null $access_count
 * @property array|null $metadata
 */

class Memory extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'workspace_id',  // immutable once set — never moved between workspaces
        'type',
        'content',
        'normalized_content',
        'content_hash',
        'importance',
        'confidence',
        'decay_score',
        'last_accessed_at',
        'last_reinforced_at',
        'archived_at',
        'access_count',
        'status',
        'metadata',
    ];

    protected $casts = [
        'workspace_id'       => 'integer',
        'metadata'           => 'array',
        'last_accessed_at'   => 'datetime',
        'last_reinforced_at' => 'datetime',
        'archived_at'        => 'datetime',
        'importance'         => 'decimal:4',
        'confidence'         => 'decimal:4',
        'decay_score'        => 'decimal:4',
        'access_count'       => 'integer',
    ];

    /**
     * The workspace this memory belongs to.
     * workspace_id is immutable — once set it never changes.
     * Export → Import is the only migration path.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'workspace_id');
    }
}