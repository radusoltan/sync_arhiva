<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Language;
use Elastic\Elasticsearch\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    private $elastic;
    public function __construct(Client $elastic){
        parent::__construct();
        $this->elastic = $elastic;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach(Language::all() as $language) {

            $categories = Category::where('IdLanguage', $language->Id)->get();
            foreach ($categories as $category) {

                $response = $this->elastic->search([
                    'index' => 'categories',
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['term' => ['slug.keyword' => $category->ShortName]],
                                ['term' => ['language.keyword' => $language->Code]],
                            ],
                        ],
                    ],
                ]);
                dump($response->asObject()->hits->total->value > 0);
//                $this->elastic->index([
//                    'index' => 'categories',
//                    'type' => '_doc',
//                    'body' => [
//                        'category_id' => $category->Number,
//                        'name' => $category->Name,
//                        'slug' => Str::slug($category->Name),
//                        'language' => $category->language->Code,
//                        'in_menu' => false
//                    ]
//                ]);
            }

        }
    }
}
