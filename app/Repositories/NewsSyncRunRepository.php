<?php

namespace App\Repositories;

use App\Models\NewsSyncRun;
use Illuminate\Database\Eloquent\Collection;

class NewsSyncRunRepository
{
    public function __construct(
        protected NewsSyncRun $model
    ) {}

    public function create(array $data): NewsSyncRun
    {
        return $this->model->create($data);
    }

    public function update(NewsSyncRun $syncRun, array $data): bool
    {
        return $syncRun->update($data);
    }

    public function updateWithStatus(string $status, NewsSyncRun $syncRun, int $created, int $skipped, array $errors = []): bool
    {
        $total = $created + $skipped;

        return $syncRun->update(
            [
                'status' => 'completed',
                'completed_at' => now(),
                'articles_created' => $created,
                'articles_skipped' => $skipped,
                'error_message' => $errors['error_message'] ?? null,
                'metadata' => [
                    'sync_duration_seconds' => now()->diffInSeconds($syncRun->started_at),
                    'processing_rate' => $total > 0 ? ($created / $total) * 100 : 0,
                    'error_trace' => $errors['error_trace'] ?? null,
                ]
            ]
        );
    }

    public function getRecentBySource(string $source, int $days = 7): Collection
    {
        return $this->model->bySource($source)
            ->recent($days)
            ->latest('started_at')
            ->get();
    }

    public function isSourceRunning(string $source): bool
    {
        return $this->model->bySource($source)
            ->running()
            ->exists();
    }

    public function getFailedSyncs(int $hours = 24): Collection
    {
        return $this->model->failed()
            ->recentHours($hours)
            ->get();
    }

    public function getLatestBySource(string $source): ?NewsSyncRun
    {
        return $this->model->bySource($source)
            ->latest('started_at')
            ->first();
    }

    public function findById(int $id): ?NewsSyncRun
    {
        return $this->model->find($id);
    }
}
