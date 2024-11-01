<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncStatus extends Model
{
    protected $fillable = [
        'status',
        'article_id'
    ];
}
