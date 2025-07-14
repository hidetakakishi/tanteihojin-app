<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailResponse extends Model
{
    protected $fillable = ['email_id', 'staff_name', 'handled_at', 'status', 'method', 'memo'];

    public function email()
    {
        return $this->belongsTo(Email::class);
    }
}
