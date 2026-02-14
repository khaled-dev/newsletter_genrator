<?php

namespace App\Services;

use Exception;
use App\Models\Article;
use Illuminate\Support\Facades\Log;

class ArticleService
{
    public function createArticlesFromDtos(array $articleDtos, string $source): array
    {
        $created = 0;
        $skipped = 0;

        $externalIds = array_map(fn($dto) => $dto->getExternalId(), $articleDtos);

        $existingIds = Article::bySource($source)
            ->whereIn('external_id', $externalIds)
            ->pluck('external_id')
            ->toArray();

        $articlesToCreate = [];

        foreach ($articleDtos as $articleDto) {
            $externalId = $articleDto->getExternalId();

            // Skip if article already exists
            if (in_array($externalId, $existingIds)) {
                $skipped++;
                continue;
            }

            // Prepare for batch insert
            $articlesToCreate[] = [
                'external_id' => $externalId,
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
            $created++;
        }

        // Batch insert new articles if any
        if (!empty($articlesToCreate)) {
            try {
                Article::query()->insert($articlesToCreate);
            } catch (Exception $e) {
                // Log the error for debugging
                Log::error('Batch insert failed', [
                    'source' => $source,
                    'articles_count' => count($articlesToCreate),
                    'error' => $e->getMessage()
                ]);

                // Fallback to individual inserts to identify problematic records
                $actualCreated = 0;
                foreach ($articlesToCreate as $articleData) {
                    try {
                        Article::create($articleData);
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
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }
}
