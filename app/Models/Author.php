<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $table = 'Authors';

    public function articles(){
        return $this->belongsToMany(Article::class, "ArticleAuthors","fk_author_id","fk_article_number", "id", "Number");
    }
}


