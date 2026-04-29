<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RSAKey extends Model
{
    protected $table = 'rsa_keys';

    protected $fillable = [
        'key_name',
        'public_n',
        'public_e',
        'private_d',
        'prime_p',
        'prime_q',
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
