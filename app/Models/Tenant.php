<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    // Use UUID as primary key, matching the existing tenant_scope_id platform standard
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', // Extracted from legacy tenant_scope_id
        'name',
        'slug',
        'status',
        'plan',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_scope_id', 'id');
    }

    // Workspaces conceptually belong to a tenant. However, historically they did not
    // have a tenant_scope_id column themselves. They resolved via their owner.
    // This relation serves as a proxy if/when teams gain a tenant_scope_id column.
    public function workspaces(): HasMany
    {
        return $this->hasMany(Team::class, 'tenant_scope_id', 'id');
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class, 'tenant_scope_id', 'id');
    }
}
