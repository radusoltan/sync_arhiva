<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageItem extends Model
{
    protected $table = 'package_item';
    protected $with = ['package', 'image'];

    public function package(){
        return $this->belongsTo(
            Package::class,
            'package_id',
            'id'
        );
    }

    public function image(){
        return $this->belongsTo(
            Image::class,
            'image_id',
            'Id'
        );
    }
}
