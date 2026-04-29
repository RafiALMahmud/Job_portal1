<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\EncryptsAttributesWithRSA;

class Job extends Model
{
    use HasFactory, EncryptsAttributesWithRSA;

    protected $fillable = [
        'title',
        'category_id',
        'job_type_id',
        'user_id',
        'vacancy',
        'salary',
        'location',
        'description',
        'benefits',
        'responsibility',
        'qualifications',
        'keywords',
        'experience',
        'company_name',
        'company_location',
        'company_website',
        'status',
        'isFeatured',
    ];

    protected array $rsaEncrypted = [
        'title',
        'salary',
        'location',
        'description',
        'benefits',
        'responsibility',
        'qualifications',
        'keywords',
        'experience',
        'company_name',
        'company_location',
        'company_website',
    ];

    public function jobType(){
        return $this->belongsTo(JobType::class);
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function applicants()
    {
        return $this->hasMany(\App\Models\Applicant::class, 'job_id');
    }
} 
