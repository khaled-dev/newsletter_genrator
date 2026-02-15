<?php

namespace App\Services;

use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use App\Repositories\ArticleRepository;

class ArticleService
{
    public function __construct(
        protected ArticleRepository $articleRepository
    ) {}

    public function createArticlesFromDtos(array $articleDtos, string $source): array
    {
        $created = 0;
        $skipped = 0;
        $externalIds = array_map(fn($dto) => $dto->getExternalId(), $articleDtos);
        $existingIds = $this->articleRepository->getExistingIdsBySource($externalIds, $source);
        $articlesToCreate = [];
        $seenInBatch = [];

        // Validate new articles, and prepare for batch insert
        foreach ($articleDtos as $articleDto) {
            $externalId = $articleDto->getExternalId();

            if (in_array($externalId, $existingIds)) {
                $skipped++;
                continue;
            }

            if (isset($seenInBatch[$externalId])) {
                $skipped++;
                continue;
            }

            $seenInBatch[$externalId] = true;
            $articlesToCreate[] = $this->articleRepository->mapForInsertion($articleDto);
            $created++;
        }

        if (empty($articlesToCreate)) {
            return [
                'created' => $created,
                'skipped' => $skipped,
            ];
        }

        try {
            $this->articleRepository->batchInsert($articlesToCreate);
        } catch (Exception $e) {
            // Fallback to individual inserts if batch insert fails, and log the error
            Log::error('Batch insert failed', [
                'source' => $source,
                'articles_count' => count($articlesToCreate),
                'error' => $e->getMessage()
            ]);

            $actualCreated = 0;
            foreach ($articlesToCreate as $articleData) {
                try {
                    $this->articleRepository->create($articleData);
                    $actualCreated++;
                } catch (\Exception $individualError) {
                    Log::warning('Individual article insert failed', [
                        'external_id' => $articleData['external_id'],
                        'source' => $source,
                        'error' => $individualError->getMessage()
                    ]);
                }
            }
            $created = $actualCreated;
            $skipped += (count($articlesToCreate) - $actualCreated);
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    public function searchWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->articleRepository->searchWithFilters($filters, $perPage);
    }

    public function getDistinctAuthors(): array
    {
        return $this->articleRepository->getDistinctAuthors();
    }
}
