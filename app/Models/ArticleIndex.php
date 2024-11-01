<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleIndex extends Model
{

    public $timestamps = false;
    protected $fillable = [
        'article_number',
        'elastic_id',
    ];

    public function article() {
        return $this->belongsTo(Article::class, 'article_id', 'Number');
    }
}
