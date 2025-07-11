<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Elastic\Elasticsearch\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use League\Csv\Reader;

class CSVImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:csv-import {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import articles from CSV to Elasticsearch. Use --dry-run to test without importing';



    protected $elastic;

    public function __construct(Client $elastic)
    {
        parent::__construct();
        $this->elastic = $elastic;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $csvPath = storage_path('/app/public/articole_2.csv');

        if (!file_exists($csvPath)) {
            $this->error("CSV file not found at: {$csvPath}");
            return Command::FAILURE;
        }

        $csv = Reader::createFromPath($csvPath, 'r');
        $csv->setHeaderOffset(0);

        // Check for dry-run option or ask user
        $isDryRun = $this->option('dry-run') || $this->confirm('Do you want to run in dry-run mode (no actual import)?');
        $successCount = 0;
        $errorCount = 0;

        $this->info('Starting CSV import to Elasticsearch...');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be imported');
        }

        foreach ($csv->getRecords() as $offset => $record) {
            try {
                $document = $this->transformRecord($record);

                if ($isDryRun) {
                    $this->line("Would import document with ID: {$document['article_id']} - {$document['title']}");
                } else {
                    $this->importToElasticsearch($document);
                    $this->line("Imported: {$document['title']}");
                }

                $successCount++;
            } catch (\Exception $e) {
                $this->error("Error processing record {$offset}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->info("Import completed! Success: {$successCount}, Errors: {$errorCount}");

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Transform CSV record to Elasticsearch document structure
     */
    private function transformRecord(array $record): array
    {
        return [
            'article_id' => !empty($record['Item ID']) ? (int)$record['Item ID'] : null,
            'title' => $record['Title'] ?? '',
            'slug' => $record['Slug'] ?? '',
            'content' => $record['Content Text'] ?? '',
            'lead' => $record['Lead text'] ?? '',
            'language' => 'ro', // Assuming Romanian based on content
            'published_at' => $this->parseDate($record['Manual Data'] ?? null),
            'tg_id' => $record['Collection ID'] ?? null,
            'category' => $this->transformCategory($record['Category'] ?? ''),
            'authors' => $this->transformAuthor($record['Author'] ?? ''),
            'images' => $this->transformImages($record),
        ];
    }

    /**
     * Transform category string to category object
     */
    private function transformCategory(string $categoryName): ?array
    {
        if (empty($categoryName)) {
            return null;
        }

        return [
            'id' => crc32($categoryName), // Generate a simple ID based on name
            'name' => $categoryName,
            'slug' => Str::slug($categoryName)
        ];
    }

    /**
     * Transform author string to authors array
     */
    private function transformAuthor(string $authorName): array
    {
        if (empty($authorName)) {
            return [];
        }

        // Handle case where author might be "first-name last-name" or just username
        $nameParts = explode(' ', $authorName, 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        return [
            [
                'id' => crc32($authorName), // Generate a simple ID
                'full_name' => $authorName,
                'first_name' => $firstName,
                'last_name' => $lastName
            ]
        ];
    }

    /**
     * Transform image data to images array
     */
    private function transformImages(array $record): array
    {
        $mainImage = $record['Main Image'] ?? '';

        if (empty($mainImage)) {
            return [];
        }

        // Extract filename from URL
        $fileName = basename(parse_url($mainImage, PHP_URL_PATH));

        return [
            [
                'id' => crc32($mainImage), // Generate a simple ID
                'fileName' => $fileName,
                'source' => $mainImage,
                'description' => $record['Short Description'] ?? '',
                'is_default' => true,
                'photographer' => null,
                'width' => null,
                'height' => null
            ]
        ];
    }

    /**
     * Parse date string to proper format
     */
    private function parseDate(?string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            $raw = $dateString;
            $cleaned = preg_replace('/\s*\(.*\)$/', '', $raw);
            // Parse the date format from CSV: "Sun Oct 06 2024 21:51:38 GMT+0000 (Coordinated Universal Time)"
            $date = Carbon::parse($cleaned);
            return $date->toISOString();
        } catch (\Exception $e) {
            $this->warn("Could not parse date: {$dateString}");
            return null;
        }
    }

    /**
     * Clean HTML content and prepare for indexing
     */
    private function cleanContent(string $content): string
    {
        // Remove HTML tags but keep the text content
        $cleaned = strip_tags($content);

        // Clean up extra whitespace
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);

        return trim($cleaned);
    }

    /**
     * Import document to Elasticsearch
     */
    private function importToElasticsearch(array $document): void
    {
        $params = [
            'index' => 'articles',
            'id' => $document['article_id'], // Use article_id as document ID
            'body' => $document
        ];

        $response = $this->elastic->index($params);

        if (!isset($response['result']) || !in_array($response['result'], ['created', 'updated'])) {
            throw new \Exception("Failed to index document: " . json_encode($response));
        }
    }
}
