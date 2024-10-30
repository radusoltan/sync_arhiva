<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $table = 'Images';

    public function articles() {
        return $this->belongsToMany(
            Article::class,
            'ArticleImages',
            'IdImage',
            "NrArticle",
            "Id",
            "Number"
        )->withPivot('is_default');
    }
}
