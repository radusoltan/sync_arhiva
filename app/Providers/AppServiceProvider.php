<?php

namespace App\Providers;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Client::class, function ($app){
            return ClientBuilder::create()
                ->setHosts(["https://165.22.89.204:9200"])
                ->setApiKey("QUkxQnJKUUJEMTI0UWlTVkNsNXU6NmJaaHE3UHdSdWEwd1B5SkNFamg5UQ==")
                ->setSSLVerification(false)
                ->build();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
