<?php

namespace App\Services\NewsSource;

use App\DTOs\Article;
use App\Enums\Sources;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\NewsSource\Contracts\NewsSourceContract;

class NewsAPIGateway implements NewsSourceContract
{
    private string $apiKey;
    private string $baseUrl = 'https://newsapi.org/v2';
    private array $data = [];

    public function __construct()
    {
        $this->apiKey = config('services.news_api.key');
    }

    public function fetchArticles(): self
    {
        try {
            $response = Http::timeout(30)
                ->retry(3, 100)
                ->get("{$this->baseUrl}/everything", [
                    'apiKey' => $this->apiKey,
                    'pageSize' => 100,
                    'language' => 'en',
                    'sortBy' => 'publishedAt',
                    'q' => '*',
                ]);

            if ($response->successful()) {
                $this->data = $response->json()['articles'] ?? [];
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
                external_id: md5($article['url']),
                title: $article['title'],
                description: $article['description'],
                content: $article['content'],
                url: $article['url'],
                image_url: $article['urlToImage'],
                author_name: $article['author'],
                published_at: $article['publishedAt'],
                source: Sources::NEWS_API->value,
            );
        }, $this->data);
    }
}
