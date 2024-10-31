<?php

use App\Http\Controllers\ImportController;
use App\Models\Article;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
//    dump('here');
    $article = Article::where([
        ['IdLanguage', 15],
        ['type', 'stiri']
    ])
        ->limit(20)->get();



    return \App\Http\Resources\ArticleResource::collection($article);

//    return \App\Http\Resources\ArticleResource::collection(
//        Article::orderBy('PublishDate', 'desc')
//            ->with('fields', 'category', "language", "authors", "images")
//            ->paginate()
//    );
});

Route::get('/import', [ImportController::class, 'import']);
