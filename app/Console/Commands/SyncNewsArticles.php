<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NewsSyncService;
use App\Services\NewsSource\NewsAPIGateway;
use App\Services\NewsSource\NyTimesGateway;
use App\Services\NewsSource\GuardianGateway;

class SyncNewsArticles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'news:sync
                            {--source= : Sync specific source only (guardian, news_api, ny_times)}
                            {--stats : Show recent sync statistics ONLY}';

    /**
     * The console command description.
     */
    protected $description = 'Sync articles from news sources (Guardian, NewsAPI, NY Times)';

    protected NewsSyncService $syncService;

    public function __construct(NewsSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('stats')) {
            $this->showStats();
            return;
        }

        $source = $this->option('source');

        if ($source) {
            $this->syncSingleSource($source);
        } else {
            $this->syncAllSources();
        }
    }

    protected function syncAllSources(): void
    {
        $this->info('🚀 Starting news sync for all sources...');

        $sources = ['guardian', 'news_api', 'ny_times'];

        foreach ($sources as $source) {
            if ($this->syncService->isSourceCurrentlyRunning($source)) {
                $this->warn("⚠️  Skipping {$source} - sync already running");
                continue;
            }

            $this->line("📡 Syncing {$source}...");
            $this->syncService->syncSource($source, $this->createGateway($source));
        }

        $this->info('✅ News sync completed for all sources!');
        $this->showRecentStats();
    }

    protected function syncSingleSource(string $source): void
    {
        if (!in_array($source, ['guardian', 'news_api', 'ny_times'])) {
            $this->error("❌ Invalid source: {$source}");
            return;
        }

        if ($this->syncService->isSourceCurrentlyRunning($source)) {
            $this->warn("⚠️  Sync for {$source} is already running");
            return;
        }

        $this->info("🚀 Starting news sync for {$source}...");
        $this->syncService->syncSource($source, $this->createGateway($source));
        $this->info("✅ News sync completed for {$source}!");
    }

    protected function createGateway(string $source): object
    {
        return match($source) {
            'guardian' => new GuardianGateway(),
            'news_api' => new NewsAPIGateway(),
            'ny_times' => new NyTimesGateway(),
            default => throw new \InvalidArgumentException("Unknown source: {$source}")
        };
    }

    protected function showStats(): void
    {
        $stats = $this->syncService->getRecentSyncStats(7);

        $this->info('📊 Recent Sync Statistics (Last 7 days)');
        $this->line('');

        foreach ($stats as $source => $data) {
            $this->line("📡 <fg=cyan>{$source}</>");
            $this->line("   Total runs: {$data['total_runs']}");
            $this->line("   Successful: <fg=green>{$data['successful_runs']}</>");
            $this->line("   Failed: <fg=red>{$data['failed_runs']}</>");
            $this->line("   Articles created: {$data['total_articles_created']}");
            $this->line("   Articles skipped: {$data['total_articles_skipped']}");
            $this->line("   Last sync: " . ($data['last_sync'] ? $data['last_sync']->format('Y-m-d H:i:s') : 'Never'));
            $this->line("   Status: " . ($data['last_sync_status'] ?: 'N/A'));
            $this->line('');
        }

        // Show recent failures
        $failures = $this->syncService->getFailedSyncs(24);
        if (!empty($failures)) {
            $this->error('⚠️  Recent Failures (Last 24 hours):');
            foreach ($failures as $failure) {
                $this->line("   {$failure['source']} at {$failure['started_at']}: {$failure['error_message']}");
            }
        }
    }

    protected function showRecentStats(): void
    {
        $stats = $this->syncService->getRecentSyncStats(1);

        $this->line('');
        $this->info('📊 Sync Results:');

        foreach ($stats as $source => $data) {
            $this->line("   {$source}: Created {$data['total_articles_created']}, Skipped {$data['total_articles_skipped']}");
        }
    }
}
