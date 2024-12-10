<?php

namespace App\Console\Commands;

use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\ArticleIndex;
use App\Models\Image;
use App\Models\Language;
use App\Models\SyncStatus;
use App\Models\SystemPreference;
use App\Services\ArticleService;
use App\Services\ImageService;
use Elastic\Elasticsearch\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Exceptions\DecoderException;
use function Laravel\Prompts\text;
use Intervention\Image\Laravel\Facades\Image as ImageManager;

class SyncArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:articles {--limit=10 : Numarul de articole pe lot} {--offset=0 : Offset-ul de pornire}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    private $elastic;
    private $imageService;

    private $articleService;
    public function __construct(Client $elastic, ImageService $imageService, ArticleService $articleService){
        parent::__construct();
        $this->elastic = $elastic;
        $this->imageService = $imageService;
        $this->articleService = $articleService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $limit = (int) $this->option('limit');

        $this->info("Sincronizare articole - limit: $limit");

        foreach(Language::all() as $language) {

            $articles = Article::where([
                ['type', 'stire'],
                ['IdLanguage', $language->Id],
                ['Published', 'Y']
            ])
                ->whereDoesntHave('syncStatus', function($query){
                    $query->where('status','completed');
                })
                ->limit($limit)
                ->get();

            foreach($articles as $article) {

                // Elasticsearch actions

                if (!$article->elasticIndex){
                    $response = $this->elastic->index([
                        'index' => 'articles',
                        'type' => "_doc",
                        'body' => new ArticleResource($article),
                    ]);
                    $this->info("Articol $article->Number adaugat in elastic cu indexul {$response['_id']}");
                } else {
                    $response = $this->elastic->update([
                        'index' => 'articles',
                        'id' => $article->elasticIndex->elastic_id,
                        'body'  => [
                            'doc' => new ArticleResource($article),
                        ]
                    ]);

                    $this->info("Articol $article->Number updatat in elastic cu indexul {$response['_id']}");
                }

                foreach ($article->images as $articleImage) {
                    $result = $this->imageService->OptimizeImage($articleImage);

                    if (is_array($result)) {
                        $this->info("Imaginea $articleImage->ImageFileName a fost transferata");
                    }
                }

                ArticleIndex::updateOrCreate([
                    'article_number' => $article->Number,
                    'elastic_id' => $response['_id'], // ID-ul returnat de Elasticsearch
                    'language' => $language->Code
                ]);

                SyncStatus::updateOrCreate([
                    'article_id' => $article->Number,
                    'status' => 'completed'
                ]);
            }
        }

        $this->info("\nSincronizarea s-a încheiat!");
    }
}
