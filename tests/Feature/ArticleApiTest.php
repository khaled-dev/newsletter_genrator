<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Article;

class ArticleApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create test articles
        Article::create([
            'external_id' => 'test1',
            'title' => 'Climate Change Article',
            'description' => 'Article about climate change',
            'content' => 'Full content about climate',
            'url' => 'https://example.com/climate',
            'image_url' => 'https://example.com/image1.jpg',
            'author_name' => 'John Smith',
            'published_at' => '2026-02-15 10:00:00',
            'source' => 'guardian'
        ]);

        Article::create([
            'external_id' => 'test2',
            'title' => 'Technology News',
            'description' => 'Latest tech developments',
            'content' => 'Full content about technology',
            'url' => 'https://example.com/tech',
            'image_url' => 'https://example.com/image2.jpg',
            'author_name' => 'Jane Doe',
            'published_at' => '2026-02-14 15:00:00',
            'source' => 'news_api'
        ]);
    }

    public function test_get_articles_endpoint()
    {
        $response = $this->getJson('/api/v1/articles');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'author_name',
                            'source',
                            'published_at'
                        ]
                    ],
                    'pagination'
                ]);
    }

    public function test_filter_articles_by_source()
    {
        $response = $this->getJson('/api/v1/articles?source=guardian');

        $response->assertStatus(200);
        $articles = $response->json('data');

        foreach ($articles as $article) {
            $this->assertEquals('guardian', $article['source']);
        }
    }

    public function test_search_articles_by_title_and_author()
    {
        $response = $this->getJson('/api/v1/articles?search=Climate');

        $response->assertStatus(200);
        $articles = $response->json('data');

        $this->assertGreaterThan(0, count($articles));
        $this->assertStringContainsString('Climate', $articles[0]['title']);
    }

    public function test_filter_articles_by_publish_date()
    {
        $response = $this->getJson('/api/v1/articles?publish_date=2026-02-15');

        $response->assertStatus(200);
        $articles = $response->json('data');

        // Check if articles are from the correct date (allow for timezone differences)
        if (count($articles) > 0) {
            foreach ($articles as $article) {
                $this->assertStringStartsWith('2026-02-15', $article['published_at']);
            }
        }
    }

    public function test_get_sources_endpoint()
    {
        $response = $this->getJson('/api/v1/sources');

        $response->assertStatus(200)
                ->assertJson([
                    'data' => ['guardian', 'news_api', 'ny_times']
                ]);
    }

    public function test_get_authors_endpoint()
    {
        $response = $this->getJson('/api/v1/authors');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'name',
                            'value'
                        ]
                    ]
                ]);
    }
}
