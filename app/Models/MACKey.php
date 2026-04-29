<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MACKey extends Model
{
    protected $table = 'mac_keys';

    protected $fillable = [
        'key_name',
        'key_value',
        'is_active',
        'rotated_at',
        'revoked_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rotated_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];
}
