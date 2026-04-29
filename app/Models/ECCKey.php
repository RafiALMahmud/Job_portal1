<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ECCKey extends Model
{
    protected $table = 'ecc_keys';

    protected $fillable = [
        'user_id',
        'public_x',
        'public_y',
        'private_d',
        'private_d_encrypted',
        'curve_name',
        'is_active',
        'rotated_at',
        'revoked_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rotated_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
