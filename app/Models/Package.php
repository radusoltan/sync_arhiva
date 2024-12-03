<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $table = 'package';

    public function articles()
    {
        return $this->belongsToMany(
            Article::class,
            'package_article_package', // numele tabelului pivot
            'package_id',              // coloana curentă din pivot
            'article_id'               // coloana din pivot care face legătura cu modelul țintă
        );
    }

    public function items()
    {
        return $this->hasMany(
            PackageItem::class,
            'package_id', // Foreign key în tabela `package_item`
            'id'          // Primary key în tabela `package`
        );
    }
}
