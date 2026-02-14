<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class NewsSyncRun extends Model
{
    protected $fillable = [
        'source',
        'status',
        'started_at',
        'completed_at',
        'articles_fetched',
        'articles_created',
        'articles_skipped',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'json',
        'articles_fetched' => 'integer',
        'articles_created' => 'integer',
        'articles_skipped' => 'integer',
    ];

    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', 'running');
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('started_at', '>=', now()->subDays($days));
    }

    public function scopeRecentHours(Builder $query, int $hours = 24): Builder
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }

    public function getDurationAttribute(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->completed_at->diffInSeconds($this->started_at);
        }
        return null;
    }
    public function getSuccessRateAttribute(): float
    {
        if ($this->articles_fetched > 0) {
            return ($this->articles_created / $this->articles_fetched) * 100;
        }
        return 0.0;
    }
}
