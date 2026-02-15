<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Article extends Model
{
    protected $fillable = [
        'external_id',
        'title',
        'description',
        'content',
        'url',
        'image_url',
        'author_name',
        'published_at',
        'source',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    public function scopeByAuthor(Builder $query, string $author): Builder
    {
        return $query->where('author_name', 'ILIKE', "%{$author}%");
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->whereRaw('to_tsvector(title || \' \' || coalesce(description, \'\')) @@ plainto_tsquery(?)', [$search]);
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('published_at', 'desc');
    }
}
