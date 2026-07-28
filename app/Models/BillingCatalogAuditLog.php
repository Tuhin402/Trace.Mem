<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingCatalogAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'action',
        'entity_type',
        'entity_id',
        'before',
        'after',
        'performed_by',
        'ip_address',
        'request_id',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'performed_by' => 'integer',
    ];

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
