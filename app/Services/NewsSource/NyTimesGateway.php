<?php

namespace App\Services\NewsSource;

use App\DTOs\Article;
use App\Enums\Sources;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\NewsSource\Contracts\NewsSourceContract;

class NyTimesGateway implements NewsSourceContract
{
    private string $apiKey;
    private string $baseUrl = 'https://api.nytimes.com/svc/news/v3/content/all';
    private array $data = [];

    public function __construct()
    {
        $this->apiKey = config('services.ny_times.key');
    }

    public function fetchArticles(): self
    {
        try {
            $response = Http::timeout(30)
                ->retry(3, 100)
                ->get("{$this->baseUrl}/all.json", [
                    'api-key' => $this->apiKey,
                    'limit' => 100,
                ]);

            if ($response->successful()) {
                $this->data = $response->json()['results'] ?? [];
            }

            Log::error('NewsAPI fetch failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $this;
        } catch (\Exception $e) {
            Log::error('NewsAPI exception', ['error' => $e->getMessage()]);
            return $this;
        }
    }

    public function normalizeArticle(): array
    {
        return array_map(function (array $article) {
            return new Article(
                 external_id: md5($article['slug_name']),
                 title: $article['title'],
                 description: $article['abstract'],
                 content: null,
                 url: $article['url'],
                 image_url: $article['multimedia'][0]['url'] ?? null,
                 author_name: $article['byline'],
                 published_at: $article['published_date'],
                 source: Sources::NY_TIMES->value,
            );
        }, $this->data);
    }
}
