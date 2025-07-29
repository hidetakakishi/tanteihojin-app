<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneCallResponse extends Model
{
    protected $fillable = [
        'phone_call_id',
        'status',
        'staff_name',
        'handled_at',
        'method',
        'memo',
    ];

    // 必要があれば、どの電話履歴に属するかのリレーション（オプション）
    public function phoneCall()
    {
        return $this->belongsTo(PhoneCall::class);
    }
}