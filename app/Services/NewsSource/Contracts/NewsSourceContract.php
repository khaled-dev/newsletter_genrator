<?php

namespace App\Services\NewsSource\Contracts;

interface NewsSourceContract
{
    public function fetchArticles(): self;
    public function normalizeArticle(): array;
}
