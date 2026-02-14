<?php

namespace App\Services;

use Exception;
use App\Models\NewsSyncRun;
use Illuminate\Support\Facades\Log;
use App\Services\NewsSource\NewsAPIGateway;
use App\Services\NewsSource\NyTimesGateway;
use App\Services\NewsSource\GuardianGateway;
use App\Services\NewsSource\Contracts\NewsSourceContract;

class NewsSyncService
{
    protected array $gateways = [
        'guardian' => GuardianGateway::class,
        'news_api' => NewsAPIGateway::class,
        'ny_times' => NyTimesGateway::class,
    ];

    public function __construct(
        protected ArticleService $articleService
    ) {}

    public function syncAllSources(): void
    {
        foreach ($this->gateways as $source => $gatewayClass) {
            $this->syncSource($source, new $gatewayClass());
        }
    }

    public function syncSource(string $source, NewsSourceContract $gateway): void
    {
        $syncRun = NewsSyncRun::create([
            'source' => $source,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $articles = $gateway->fetchArticles()->normalizeArticle();

            $syncRun->update(['articles_fetched' => count($articles)]);

            // Use ArticleService to handle article creation
            $result = $this->articleService->createArticlesFromDtos($articles, $source);
            $created = $result['created'];
            $skipped = $result['skipped'];

            $syncRun->update([
                'status' => 'completed',
                'completed_at' => now(),
                'articles_created' => $created,
                'articles_skipped' => $skipped,
                'metadata' => [
                    'sync_duration_seconds' => now()->diffInSeconds($syncRun->started_at),
                    'processing_rate' => count($articles) > 0 ? ($created / count($articles)) * 100 : 0,
                ]
            ]);

            Log::info("News sync completed", [
                'source' => $source,
                'fetched' => count($articles),
                'created' => $created,
                'skipped' => $skipped,
            ]);

        } catch (Exception $e) {
            $syncRun->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
                'metadata' => [
                    'sync_duration_seconds' => now()->diffInSeconds($syncRun->started_at),
                    'error_trace' => $e->getTraceAsString(),
                ]
            ]);

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
            $recentRuns = NewsSyncRun::bySource($source)
                ->recent($days)
                ->latest('started_at')
                ->get();

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
        return NewsSyncRun::bySource($source)
            ->running()
            ->exists();
    }

    public function getFailedSyncs(int $hours = 24): array
    {
        return NewsSyncRun::failed()
            ->recentHours($hours)
            ->get()
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
