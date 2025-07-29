<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneCall extends Model
{
    protected $fillable = [
        'call_date',
        'call_time',
        'staff_name',
        'region',
        'customer_name',
        'customer_phone',
        'gender',
        'request_type',
        'request_detail',
        'staff_response',
        'customer_reply',
        'site',
    ];

    // 対応状況（レスポンス）との関係
    public function responses()
    {
        return $this->hasMany(PhoneCallResponse::class);
    }

    // 最新の対応状況だけ取得
    public function latestResponse()
    {
        return $this->hasOne(PhoneCallResponse::class)->latestOfMany();
    }
}
