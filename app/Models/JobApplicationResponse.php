<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplicationResponse extends Model
{
    protected $fillable = [
        'job_application_id','staff_name','handled_at','status','method','memo'
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    public function application() {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }
}