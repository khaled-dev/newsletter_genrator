<?php

namespace App\Repositories;

use App\Models\Article;
use App\DTOs\Article as ArticleDto;
use Illuminate\Pagination\LengthAwarePaginator;

class ArticleRepository
{
    public function __construct(
        protected Article $model
    ) {}

    public function getExistingIdsBySource(array $externalIds, string $source): array
    {
        return $this->model->bySource($source)
            ->whereIn('external_id', $externalIds)
            ->pluck('external_id')
            ->toArray();
    }

    public function batchInsert(array $articles): bool
    {
        return $this->model->insert($articles);
    }

    public function create(array $articleData): Article
    {
        return $this->model->create($articleData);
    }

    public function mapForInsertion(ArticleDto $articleDto): array
    {
        return [
            'external_id' => $articleDto->getExternalId(),
            'title' => $articleDto->getTitle(),
            'description' => $articleDto->getDescription(),
            'content' => $articleDto->getContent(),
            'url' => $articleDto->getUrl(),
            'image_url' => $articleDto->getImageUrl(),
            'author_name' => $articleDto->getAuthorName(),
            'published_at' => $articleDto->getPublishedAt(),
            'source' => $articleDto->getSource(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function searchWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (isset($filters['source'])) {
            $query->bySource($filters['source']);
        }

        if (isset($filters['author'])) {
            $query->byAuthor($filters['author']);
        }

        if (isset($filters['publish_date'])) {
            $query->whereDate('published_at', $filters['publish_date']);
        }

        if (isset($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $search = $filters['search'];
                $q->where('title', 'ILIKE', "%{$search}%")
                  ->orWhere('author_name', 'ILIKE', "%{$search}%");
            });
        }

        $query->latest();

        return $query->paginate($perPage);
    }

    public function getDistinctAuthors(): array
    {
        return $this->model->query()
            ->select('author_name')
            ->whereNotNull('author_name')
            ->where('author_name', '!=', '')
            ->distinct()
            ->pluck('author_name')
            ->map(fn($author) => [
                'name' => $author,
                'value' => $author,
            ])
            ->toArray();
    }
}
