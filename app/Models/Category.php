<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'Sections';

    public function articles(){
        return $this->belongsTo(Article::class);
    }

    public function language(){
        return $this->belongsTo(Language::class, "IdLanguage", "Id");
    }
}
