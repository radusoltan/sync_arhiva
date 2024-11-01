<?php

namespace App\Jobs;

use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\SyncStatus;
use App\Services\ImageService;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SyncArticleJob implements ShouldQueue
{
    use Queueable;
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    protected $article;

    /**
     * Create a new job instance.
     */
    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $elastic = ClientBuilder::create()
            ->setHosts(config('services.elastic.hosts'))
            ->setApiKey(config('services.elastic.api_key'))
            ->setSSLVerification(false)
            ->build();
        $imageService = app(ImageService::class);
        foreach ($this->article->image as $image) {
            Http::post('http://localhost:8001/api/import-image',[
                'image' => $image->ImageFileName,
            ]);
        }

        $elastic->index([
            'index' => 'articles',
            'type' => '_doc',
            'id' => $this->article->Number,
            'body' => new ArticleResource($this->article),
        ]);

        SyncStatus::updateOrCreate([
            ['article_id' => $this->article->Number],
            ['status' => 'completed']
        ]);
    }
}
