<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $fillable = [
        'name','region','phone','email','age','gender',
        'desired_type','desired_area','site','page_url',
        'reason','experience','qualifications','personality',
        'body','sent_at','deleted_flag',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'deleted_flag' => 'boolean',
    ];

    public function responses() {
        return $this->hasMany(JobApplicationResponse::class)->orderByDesc('id');
    }

    public function latestResponse() {
        return $this->hasOne(JobApplicationResponse::class)->latestOfMany();
    }
}