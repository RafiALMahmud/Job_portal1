<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employer extends Model
{
    use HasFactory;

    protected $primaryKey = 'employer_id';

    protected $fillable = [
        'user_id',
        'company_name',
        'company_location',
        'company_website',
        'company_description',
        'company_logo',
        'company_size',
        'industry',
        'founded_year'
    ];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with Jobs
    public function jobs()
    {
        return $this->hasManyThrough(Job::class, User::class, 'id', 'user_id');
    }
} 