<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $fillable = ['from', 'to', 'subject', 'sent_at', 'body', 'site', 'deleted_flag'];

    public function response()
    {
        return $this->hasOne(EmailResponse::class);
    }

    public function latestResponse()
    {
        return $this->hasOne(EmailResponse::class)->latestOfMany('created_at');
    }

    // すべての対応履歴（← 追加）
    public function responses()
    {
        return $this->hasMany(EmailResponse::class);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('deleted_flag', false);
    }
}
