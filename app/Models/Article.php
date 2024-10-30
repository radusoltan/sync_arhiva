<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = "Articles";

    public function fields(){
        return $this->hasOne(Xstiri::class, 'NrArticle', 'Number');
    }

    public function category(){
        return $this->hasOne(Category::class, 'Number', "NrSection");
    }

    public function images(){
        return $this->belongsToMany(
            Image::class,
            'ArticleImages',
            'NrArticle',
            'IdImage',
            "Number",
            "Id"
        )->withPivot('is_default');
    }

    public function language(){
        return $this->belongsTo(Language::class, 'IdLanguage', 'Id');
    }

    public function authors(){
        return $this->belongsToMany(Author::class, "ArticleAuthors","fk_article_number", "fk_author_id", "Number", "id");
    }
}
