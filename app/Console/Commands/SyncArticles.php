<?php

namespace App\Console\Commands;

use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\Language;
use App\Services\ImageService;
use Elastic\Elasticsearch\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:articles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    private $elastic;
    private $imageService;
    public function __construct(Client $elastic, ImageService $imageService){
        parent::__construct();
        $this->elastic = $elastic;
        $this->imageService = $imageService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $this->info('Indexing all articles. This might take a while...');

        foreach(Language::all() as $language) {
            $articles = Article::where([
                ['type', 'stiri'],
                ['IdLanguage', $language->Id],
            ])
                ->limit(10)->get();

            foreach($articles as $article) {

                foreach($article->images as $image) {
                    Http::post(env('APP_ARHIVA_URL').'/api/import-image',[
                        'image' => $image->ImageFileName
                    ]);
                }

                $this->elastic->index([
                    'index' => 'articles',
                    'type' => '_doc',
                    'id' => $article->id,
                    'body' => new ArticleResource($article),
                ]);
                // PHPUnit-style feedback
                $this->output->write('.');
            }
        }

        $this->info("\nDone!");
    }
}
