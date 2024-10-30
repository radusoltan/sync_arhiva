<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $table = 'Languages';

    public function articles() {
        return $this->hasMany(Article::class, "IdLanguage", "Id");
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'IdLanguage', 'Id');
    }
}
