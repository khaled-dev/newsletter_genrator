<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\NewsSource\GuardianGateway;
use App\Services\NewsSource\NewsAPIGateway;
use App\Services\NewsSource\NyTimesGateway;
use Illuminate\Support\Facades\Http;

class GatewayTest extends TestCase
{
    public function test_guardian_gateway_fetches_and_normalizes_articles()
    {
        // Mock Guardian API response
        Http::fake([
            'content.guardianapis.com/*' => Http::response([
                'response' => [
                    'results' => [
                        [
                            'id' => 'test-id-1',
                            'webTitle' => 'Test Guardian Article',
                            'webUrl' => 'https://guardian.com/test',
                            'pillarName' => 'Test Author',
                            'webPublicationDate' => '2026-02-15T10:00:00Z'
                        ]
                    ]
                ]
            ])
        ]);

        $gateway = new GuardianGateway();
        $articles = $gateway->fetchArticles()->normalizeArticle();

        $this->assertCount(1, $articles);
        $this->assertEquals('Test Guardian Article', $articles[0]->getTitle());
        $this->assertEquals('guardian', $articles[0]->getSource());
    }

    public function test_news_api_gateway_fetches_and_normalizes_articles()
    {
        // Mock NewsAPI response
        Http::fake([
            'newsapi.org/*' => Http::response([
                'articles' => [
                    [
                        'url' => 'https://newsapi.com/test',
                        'title' => 'Test NewsAPI Article',
                        'description' => 'Test description',
                        'content' => 'Test content',
                        'urlToImage' => 'https://image.jpg',
                        'author' => 'NewsAPI Author',
                        'publishedAt' => '2026-02-15T10:00:00Z'
                    ]
                ]
            ])
        ]);

        $gateway = new NewsAPIGateway();
        $articles = $gateway->fetchArticles()->normalizeArticle();

        $this->assertCount(1, $articles);
        $this->assertEquals('Test NewsAPI Article', $articles[0]->getTitle());
        $this->assertEquals('news_api', $articles[0]->getSource());
    }

    public function test_ny_times_gateway_fetches_and_normalizes_articles()
    {
        // Mock NY Times API response
        Http::fake([
            'api.nytimes.com/*' => Http::response([
                'results' => [
                    [
                        'slug_name' => 'test-slug',
                        'title' => 'Test NY Times Article',
                        'abstract' => 'Test abstract',
                        'url' => 'https://nytimes.com/test',
                        'multimedia' => [
                            ['url' => 'https://nytimes-image.jpg']
                        ],
                        'byline' => 'By NY Times Author',
                        'published_date' => '2026-02-15'
                    ]
                ]
            ])
        ]);

        $gateway = new NyTimesGateway();
        $articles = $gateway->fetchArticles()->normalizeArticle();

        $this->assertCount(1, $articles);
        $this->assertEquals('Test NY Times Article', $articles[0]->getTitle());
        $this->assertEquals('ny_times', $articles[0]->getSource());
    }
}
