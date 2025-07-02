<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapedArticle extends Model
{
    protected $fillable = ['url','scraping_source_id','title','published_at'];

    public function source()
    {
        return $this->belongsTo(ScrapingSource::class, 'scraping_source_id');
    }
}
