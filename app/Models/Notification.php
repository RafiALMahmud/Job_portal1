<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\EncryptsAttributesWithRSA;

class Notification extends Model
{
    use HasFactory, EncryptsAttributesWithRSA;

    protected array $rsaEncrypted = [
        'message',
    ];

    protected $fillable = [
        'user_id',
        'job_id',
        'applicant_id',
        'message',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean'
    ];

    // Relationship with User (Employer)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with Job
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    // Relationship with Applicant
    public function applicant()
    {
        return $this->belongsTo(Applicant::class, 'applicant_id', 'applicant_id');
    }
} 
