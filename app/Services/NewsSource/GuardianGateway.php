<?php

namespace App\Services\NewsSource;

use App\DTOs\Article;
use App\Enums\Sources;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\NewsSource\Contracts\NewsSourceContract;

class GuardianGateway implements NewsSourceContract
{
    private string $apiKey;
    private string $baseUrl = 'https://content.guardianapis.com';
    private array $data = [];

    public function __construct()
    {
        $this->apiKey = config('services.guardian.key');
    }

    public function fetchArticles(): self
    {
        try {
            $response = Http::timeout(30)
                ->retry(3, 100)
                ->get("{$this->baseUrl}/search", [
                    'api-key' => $this->apiKey,
                    'page-size' => 100,
                    'language' => 'en',
                ]);

            if ($response->successful()) {
                $this->data = $response->json()['response']['results'] ?? [];
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
                external_id: md5($article['id']),
                title: $article['webTitle'],
                description: $article['webTitle'],
                content: null,
                url: $article['webUrl'],
                image_url: null,
                author_name: $article['pillarName'],
                published_at: $article['webPublicationDate'],
                source: Sources::GUARDIAN->value,
            );
        }, $this->data);
    }
}
