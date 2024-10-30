<?php

use App\Models\Article;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return \App\Http\Resources\ArticleResource::collection(
        Article::orderBy('PublishDate', 'desc')
            ->with('fields', 'category', "language", "authors", "images")
            ->paginate()
    );
});
