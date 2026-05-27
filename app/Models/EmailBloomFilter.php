<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailBloomFilter extends Model
{
    protected $fillable = [
        'filter_key',
        'size',
        'hash_count',
        'bitset',
    ];
    protected $casts = [
        'size' => 'integer',
        'hash_count' => 'integer',
        'bitset' => 'string',
    ];
}