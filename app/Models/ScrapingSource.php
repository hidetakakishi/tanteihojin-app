<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapingSource extends Model
{
    protected $fillable = ['name','url'];

    public function articles()
    {
        return $this->hasMany(ScrapedArticle::class);
    }
}
