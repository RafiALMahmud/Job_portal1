<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    public const PURPOSE_REGISTER = 'register_verify';
    public const PURPOSE_LOGIN = 'login_2fa';
    public const PURPOSE_FORGOT_PASSWORD = 'forgot_password';

    protected $fillable = [
        'user_id',
        'email_lookup_hash',
        'purpose',
        'code_hash',
        'expires_at',
        'attempts',
        'resend_available_at',
        'consumed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'resend_available_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
