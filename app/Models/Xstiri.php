<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Xstiri extends Model
{
    public $table = 'Xstiri';

    public function article() {
        return $this->belongsTo(Article::class, 'NrArticle');
    }
}
