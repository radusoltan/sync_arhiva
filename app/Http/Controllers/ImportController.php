<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Elastic\Elasticsearch\Client;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    private $client;

    public function __construct(Client $client){
        $this->client = $client;
    }
    public function import(){
//        $index = 'articles';
//        $params = [
//            'index' => $index,
//            'body' => [
//                'query' => [
//                    'match_all' => new \stdClass()
//                ],
//                'size' => 100
//            ]
//        ];
//        $response = $this->client->search($params);

        $articlesToSync = ArticleResource::collection(Article::orderBy('PublishDate', 'desc')
            ->with('fields', 'category', "language", "authors", "images")
            ->cursor());

        foreach($articlesToSync as $article) {


            dump($article);
        }

//        dump($response->asObject());
    }
}
