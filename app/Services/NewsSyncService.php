<?php

namespace App\Services;

use Exception;
use App\Models\NewsSyncRun;
use Illuminate\Support\Facades\Log;
use App\Services\NewsSource\NewsAPIGateway;
use App\Services\NewsSource\NyTimesGateway;
use App\Services\NewsSource\GuardianGateway;
use App\Repositories\NewsSyncRunRepository;
use App\Services\NewsSource\Contracts\NewsSourceContract;

class NewsSyncService
{
    protected array $gateways = [
        'guardian' => GuardianGateway::class,
        'news_api' => NewsAPIGateway::class,
        'ny_times' => NyTimesGateway::class,
    ];

    public function __construct(
        protected ArticleService $articleService,
        protected NewsSyncRunRepository $syncRunRepository
    ) {}

    public function syncAllSources(): void
    {
        foreach ($this->gateways as $source => $gatewayClass) {
            $this->syncSource($source, new $gatewayClass());
        }
    }

    public function syncSource(string $source, NewsSourceContract $gateway): void
    {
        $syncRun = $this->syncRunRepository->create([
            'source' => $source,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $articles = $gateway->fetchArticles()->normalizeArticle();

            $this->syncRunRepository->update($syncRun, ['articles_fetched' => count($articles)]);

            // Use ArticleService to handle article creation
            $result = $this->articleService->createArticlesFromDtos($articles, $source);
            $created = $result['created'];
            $skipped = $result['skipped'];

            $this->syncRunRepository->updateWithStatus(NewsSyncRun::STATUS_COMPLETED, $syncRun, $created, $skipped);

            Log::info("News sync completed", [
                'source' => $source,
                'fetched' => count($articles),
                'created' => $created,
                'skipped' => $skipped,
            ]);

        } catch (Exception $e) {
            $this->syncRunRepository->updateWithStatus(
                NewsSyncRun::STATUS_FAILED, $syncRun,
                0,
                0,
                ['error_message' => $e->getMessage(), 'error_trace' => $e->getTraceAsString()]
            );

            Log::error("News sync failed", [
                'source' => $source,
                'error' => $e->getMessage(),
                'sync_run_id' => $syncRun->id,
            ]);
        }
    }

    public function getRecentSyncStats(int $days = 7): array
    {
        $stats = [];

        foreach (array_keys($this->gateways) as $source) {
            $recentRuns = $this->syncRunRepository->getRecentBySource($source, $days);

            $stats[$source] = [
                'total_runs' => $recentRuns->count(),
                'successful_runs' => $recentRuns->where('status', 'completed')->count(),
                'failed_runs' => $recentRuns->where('status', 'failed')->count(),
                'total_articles_created' => $recentRuns->sum('articles_created'),
                'total_articles_skipped' => $recentRuns->sum('articles_skipped'),
                'last_sync' => $recentRuns->first()?->started_at,
                'last_sync_status' => $recentRuns->first()?->status,
            ];
        }

        return $stats;
    }

    public function isSourceCurrentlyRunning(string $source): bool
    {
        return $this->syncRunRepository->isSourceRunning($source);
    }

    public function getFailedSyncs(int $hours = 24): array
    {
        return $this->syncRunRepository->getFailedSyncs($hours)
            ->map(function ($run) {
                return [
                    'source' => $run->source,
                    'started_at' => $run->started_at,
                    'error_message' => $run->error_message,
                    'sync_run_id' => $run->id,
                ];
            })
            ->toArray();
    }
}
