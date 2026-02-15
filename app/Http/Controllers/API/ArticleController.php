<?php

namespace App\Http\Controllers\API;

use App\Enums\Sources;
use App\Http\Controllers\Controller;
use App\Services\ArticleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    public function __construct(
        protected ArticleService $articleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'author' => 'nullable|string|min:2',
            'source' => 'nullable|in:guardian,news_api,ny_times',
            'publish_date' => 'nullable|date',
            'search' => 'nullable|string|min:2',
        ]);

        $filters = [];

        if ($request->has('source')) {
            $filters['source'] = $request->source;
        }

        if ($request->has('author')) {
            $filters['author'] = $request->author;
        }

        if ($request->has('publish_date')) {
            $filters['publish_date'] = $request->publish_date;
        }

        if ($request->has('search')) {
            $filters['search'] = $request->search;
        }

        $articles = $this->articleService->searchWithFilters($filters);

        return response()->json([
            'data' => $articles->items(),
            'pagination' => [
                'current_page' => $articles->currentPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
                'last_page' => $articles->lastPage(),
                'has_more' => $articles->hasMorePages(),
            ],
        ]);
    }

    public function sources(): JsonResponse
    {
        return response()->json([
            'data' => Sources::allValues()
        ]);
    }

    public function authors(): JsonResponse
    {
        $authors = $this->articleService->getDistinctAuthors();

        return response()->json([
            'data' => $authors
        ]);
    }
}
