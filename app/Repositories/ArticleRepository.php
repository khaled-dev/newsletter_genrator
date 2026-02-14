<?php

namespace App\Repositories;

use App\Models\Article;
use App\DTOs\Article as ArticleDto;
use Illuminate\Database\Eloquent\Collection;

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

    public function findById(int $id): ?Article
    {
        return $this->model->find($id);
    }

    public function findByIdOrFail(int $id): Article
    {
        return $this->model->findOrFail($id);
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

    public function searchWithFilters(array $filters, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->model->query();

        if (isset($filters['source'])) {
            $query->bySource($filters['source']);
        }

        if (isset($filters['from_date'])) {
            $query->publishedAfter(\Carbon\Carbon::parse($filters['from_date']));
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        $query->latest();

        return $query->paginate($perPage);
    }

    public function searchByQuery(string $searchQuery, array $filters = [], int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->model->search($searchQuery);

        if (isset($filters['source'])) {
            $query->bySource($filters['source']);
        }

        if (isset($filters['from_date'])) {
            $query->publishedAfter(\Carbon\Carbon::parse($filters['from_date']));
        }

        $query->latest();

        return $query->paginate($perPage);
    }
}
