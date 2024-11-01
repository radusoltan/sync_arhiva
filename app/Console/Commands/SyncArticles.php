<?php

namespace App\Console\Commands;

use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\ArticleIndex;
use App\Models\Language;
use App\Models\SyncStatus;
use App\Services\ImageService;
use Elastic\Elasticsearch\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use function Laravel\Prompts\text;

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

        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');

        $this->info("Sincronizare articole - limit: $limit, offset: $offset");

        // Definim articolele de evitat
        $excludedNumbers = [21, 22, 23, 24, 25, 26];

        foreach(Language::all() as $language) {

            $articles = Article::where([
                ['type', 'stiri'],
                ['IdLanguage', $language->Id],
                ['Published', 'Y']
            ])
                ->whereDoesntHave('syncStatus', function($query){
                    $query->where('status','completed');
                })
                ->limit((int)$limit)
                ->offset(20)
                ->get();

            foreach($articles as $article) {

                // Verificăm dacă articolul este exclus sau deja sincronizat
                if (in_array($article->Number, $excludedNumbers) ||
                    ArticleIndex::where('article_number', $article->Number)->exists()) {
                    $this->output->write('S'); // Sărim peste articolul exclus sau deja importat
                    continue;
                }

                foreach($article->images as $image) {
                    Http::post('http://localhost:8001/api/import-image',[
                        'image' => $image->ImageFileName
                    ]);
                }

                $response = $this->elastic->index([
                    'index' => 'articles',
                    'type' => '_doc',
                    'id' => $article->Number,
                    'body' => new ArticleResource($article),
                ]);

                ArticleIndex::create([
                    'article_number' => $article->Number,
                    'elastic_id' => $response['_id'], // ID-ul returnat de Elasticsearch
                ]);

                SyncStatus::updateOrCreate([
                    'article_id' => $article->Number,
                    'status' => 'completed'
                ]);

                // PHPUnit-style feedback
                $this->output->write('.');
            }
        }

        $this->info("\nSincronizarea s-a încheiat!");
    }
}
