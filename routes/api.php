<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ArticleController;

Route::group(['prefix' => 'v1'], function () {
    Route::get('articles', [ArticleController::class, 'index']);
    Route::get('sources', [ArticleController::class, 'sources']);
    Route::get('authors', [ArticleController::class, 'authors']);
});

