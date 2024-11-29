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
use Illuminate\Support\Facades\Storage;
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
//        $excludedNumbers = [21, 22, 23, 24, 25, 26];

        foreach(Language::all() as $language) {

            $articles = Article::where([
                ['type', 'stiri'],
                ['IdLanguage', $language->Id],
                ['Published', 'Y']
            ])
                ->whereDoesntHave('syncStatus', function($query){
                    $query->where('status','completed');
                })
                ->limit($limit)
                ->offset($offset)
                ->get();

            foreach($articles as $article) {
                foreach ($article->images as $articleImage) {

                    $file = Storage::disk('alpha')->get($articleImage->ImageFileName);

                    $image = ImageManager::read($file);
                    $image->scaleDown(1000);
                    $image->save(storage_path('app/public/images/alpha/' . $articleImage->ImageFileName));
//
                    $optimizedImage = Storage::disk('public')->get('images/alpha/' . $articleImage->ImageFileName);

                    $this->info("Imaginea $articleImage->ImageFileName a fost optimizata");

                    $sshDisk = Storage::disk('sftp');

                    $sshDisk->put($articleImage->ImageFileName, $optimizedImage);

                    $this->info("Imaginea $articleImage->ImageFileName a fost transferata");

                    Storage::disk('public')->delete('images/alpha/'.$articleImage->ImageFileName);

                }

                $response = $this->elastic->update([
                    'index' => "articles",
                    'id' => $article->elasticIndex->elastic_id,
                    'body' => [
                        'doc' => new ArticleResource($article)
                    ],
                ]);
                $this->info("Elastic doc {$article->elasticIndex->elastic_id} updated");


//                $response = $this->elastic->index([
//                    'index' => 'articles',
//                    'type' => '_doc',
////                    'id' => $article->Number,
//                    'body' => new ArticleResource($article),
//                ]);
//
//                ArticleIndex::create([
//                    'article_number' => $article->Number,
//                    'elastic_id' => $response['_id'], // ID-ul returnat de Elasticsearch
//                ]);
//
                SyncStatus::updateOrCreate([
                    'article_id' => $article->Number,
                    'status' => 'completed'
                ]);

                // PHPUnit-style feedback
//                $this->output->write('.');
            }
        }

        $this->info("\nSincronizarea s-a încheiat!");
    }

    private function setPermissions($remotePath)
    {
        $connection = Storage::disk('sftp')->getAdapter()->getConnection();

        // Comandă pentru a schimba proprietarul și permisiunile
        $connection->exec("chown www-data:www-data /var/www/html/images/alpha/$remotePath");
        $connection->exec("chmod 755 /var/www/html/images/alpha/$remotePath");
    }
}
